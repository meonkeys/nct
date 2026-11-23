<!--
SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="nct">
	<NcAppContent>
		<div class="nct">
			<aside class="nct__conversations">
				<div class="nct__new">
					<input
						v-model="newQuery"
						placeholder="New message to…"
						@focus="showSuggestions = true"
						@keydown.enter.prevent="startTypedConversation"
						@keydown.escape="showSuggestions = false">
					<ul v-if="showSuggestions && suggestions.length" class="nct__suggestions">
						<li
							v-for="suggestion in suggestions"
							:key="suggestion.address"
							@mousedown.prevent="selectConversation(suggestion.address)">
							<strong>{{ suggestion.name ?? suggestion.address }}</strong>
							<span v-if="suggestion.name" class="nct__suggestion-address">{{ suggestion.address }}</span>
						</li>
					</ul>
				</div>

				<ul>
					<li
						v-for="conversation in conversations"
						:key="conversation.address"
						:class="{ 'nct__conversation--active': conversation.address === selectedAddress }"
						@click="selectConversation(conversation.address)">
						<strong>{{ conversation.name ?? conversation.address }}</strong>
						<div class="nct__preview">{{ latestBody(conversation) }}</div>
					</li>
				</ul>
			</aside>

			<section class="nct__thread">
				<ul v-if="selectedConversation" ref="messages" class="nct__messages">
					<template v-for="(message, index) in selectedConversation.messages" :key="index">
						<li v-if="isNewDay(selectedConversation.messages, index)" class="nct__day-separator">
							{{ formatDaySeparator(message.sentAt) }}
						</li>
						<li :class="'nct__message--' + message.direction">
							<span class="nct__message-body">{{ message.body }}</span>
							<span class="nct__message-time">{{ formatTime(message.sentAt) }}</span>
						</li>
					</template>
				</ul>
				<p v-else-if="loading">
					Loading…
				</p>
				<p v-else>
					Select a conversation, or start one using "New" above.
				</p>

				<p v-if="sendError" class="nct__error">
					{{ sendError }}
				</p>

				<form v-if="selectedAddress" class="nct__compose" @submit.prevent="send">
					<textarea
						ref="body"
						v-model="draftBody"
						placeholder="Message"
						rows="1"
						required
						@keydown.enter.exact.prevent="send"
						@input="growBody" />
					<NcButton type="submit">
						Send
					</NcButton>
				</form>
			</section>
		</div>
	</NcAppContent>
	</NcContent>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'

export default {
	name: 'App',

	components: {
		NcAppContent,
		NcButton,
		NcContent,
	},

	data() {
		return {
			conversations: [],
			contacts: [],
			selectedAddress: null,
			newQuery: '',
			showSuggestions: false,
			draftBody: '',
			loading: true,
			sendError: null,
			pollHandle: null,
		}
	},

	computed: {
		selectedConversation() {
			const existing = this.conversations.find((c) => c.address === this.selectedAddress)
			if (existing) {
				return existing
			}
			if (this.selectedAddress === null) {
				return null
			}
			return { address: this.selectedAddress, name: this.nameFor(this.selectedAddress), messages: [] }
		},

		suggestions() {
			const query = this.newQuery.trim().toLowerCase()
			if (query === '') {
				return []
			}

			const byAddress = new Map()
			for (const contact of this.contacts) {
				byAddress.set(contact.address, contact)
			}
			for (const conversation of this.conversations) {
				byAddress.set(conversation.address, { address: conversation.address, name: conversation.name })
			}

			return [...byAddress.values()]
				.filter((c) => (c.name ?? '').toLowerCase().includes(query) || c.address.toLowerCase().includes(query))
				.slice(0, 8)
		},
	},

	mounted() {
		this.loadConversations()
		this.loadContacts()
		this.pollHandle = setInterval(() => this.loadConversations(), 15000)
	},

	beforeUnmount() {
		clearInterval(this.pollHandle)
	},

	methods: {
		latestBody(conversation) {
			return conversation.messages[conversation.messages.length - 1].body
		},

		isNewDay(messages, index) {
			if (index === 0) {
				return true
			}
			const previousDay = new Date(messages[index - 1].sentAt * 1000).toDateString()
			const currentDay = new Date(messages[index].sentAt * 1000).toDateString()
			return previousDay !== currentDay
		},

		formatDaySeparator(sentAt) {
			const date = new Date(sentAt * 1000)
			return date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
		},

		formatTime(sentAt) {
			return new Date(sentAt * 1000).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
		},

		nameFor(address) {
			return this.conversations.find((c) => c.address === address)?.name
				?? this.contacts.find((c) => c.address === address)?.name
				?? null
		},

		selectConversation(address) {
			this.selectedAddress = address
			this.newQuery = ''
			this.showSuggestions = false
			this.scrollToBottom()
		},

		startTypedConversation() {
			const address = this.newQuery.trim()
			if (address === '') {
				return
			}
			this.selectConversation(address)
		},

		scrollToBottom() {
			this.$nextTick(() => {
				const el = this.$refs.messages
				if (el) {
					el.scrollTop = el.scrollHeight
				}
			})
		},

		growBody() {
			const el = this.$refs.body
			if (el) {
				el.style.height = 'auto'
				el.style.height = `${el.scrollHeight}px`
			}
		},

		async loadConversations() {
			const response = await axios.get(generateUrl('/apps/nct/inbox'))
			this.conversations = response.data
			if (this.selectedAddress === null && this.conversations.length > 0) {
				this.selectConversation(this.conversations[0].address)
			}
			this.loading = false
		},

		async loadContacts() {
			const response = await axios.get(generateUrl('/apps/nct/contacts'))
			this.contacts = response.data
		},

		async send() {
			this.sendError = null
			try {
				await axios.post(generateUrl('/apps/nct/messages'), {
					address: this.selectedAddress,
					body: this.draftBody,
				})
			} catch (e) {
				this.sendError = e.response?.data?.error ?? 'Failed to send message'
				return
			}
			this.draftBody = ''
			this.$nextTick(() => this.growBody())
			await this.loadConversations()
		},
	},
}
</script>

<style scoped>
.nct {
	display: flex;
	height: 100%;
}

.nct__conversations {
	width: 250px;
	border-right: 1px solid var(--color-border);
	overflow-y: auto;
}

.nct__new {
	position: relative;
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
}

.nct__new input {
	width: 100%;
}

.nct__suggestions {
	position: absolute;
	left: 8px;
	right: 8px;
	top: 100%;
	z-index: 1;
	background-color: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	box-shadow: 0 2px 8px var(--color-box-shadow);
	max-height: 300px;
	overflow-y: auto;
}

.nct__suggestions li {
	padding: 8px 12px;
	cursor: pointer;
}

.nct__suggestions li:hover {
	background-color: var(--color-background-hover);
}

.nct__suggestion-address {
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
	margin-left: 6px;
}

.nct__conversations > ul li {
	padding: 8px 12px;
	cursor: pointer;
}

.nct__conversation--active {
	background-color: var(--color-background-hover);
}

.nct__preview {
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.nct__thread {
	flex: 1;
	display: flex;
	flex-direction: column;
	padding: 12px;
}

.nct__messages {
	flex: 1;
	overflow-y: auto;
}

.nct__messages li {
	margin-top: 4px;
	margin-bottom: 4px;
	padding: 6px 10px;
	border-radius: 8px;
	max-width: 60%;
}

.nct__messages .nct__day-separator {
	max-width: none;
	width: 100%;
	text-align: center;
	color: var(--color-text-maxcontrast);
	font-size: 0.85em;
	margin-top: 16px;
}

.nct__message-time {
	display: block;
	text-align: right;
	font-size: 0.75em;
	color: var(--color-text-maxcontrast);
	margin-top: 2px;
}

.nct__message--in {
	background-color: var(--color-background-dark);
}

.nct__message--out {
	background-color: var(--color-primary-element-light);
	margin-left: auto;
}

.nct__error {
	color: var(--color-error-text);
}

.nct__compose {
	display: flex;
	align-items: flex-end;
	gap: 8px;
	margin-top: 12px;
}

.nct__compose textarea {
	flex: 1;
	resize: none;
	max-height: 200px;
	overflow-y: auto;
	line-height: 1.4;
	padding: 6px 10px;
}
</style>
