@props(['messages' => [], 'empty' => 'No messages yet.', 'id' => null])

<div class="t-chat" id="{{ $id }}" data-tenant-chat>
    <div class="t-chat-thread" data-chat-thread>
        @forelse($messages as $message)
            <div class="t-chat-message {{ ($message['is_me'] ?? false) ? 'is-me' : '' }}" data-message-id="{{ $message['id'] }}">
                <div class="t-chat-meta">
                    <span class="t-chat-author">{{ $message['author'] ?? '' }}</span>
                    <span class="t-chat-time">{{ $message['at'] ?? '' }}</span>
                </div>
                <div class="t-chat-body">{!! nl2br(e($message['body'] ?? '')) !!}</div>
                @if(!empty($message['attachments']))
                    <div class="t-chat-attachments">
                        @foreach($message['attachments'] as $attachment)
                            <a href="{{ $attachment['url'] }}" target="_blank" class="t-chat-attachment">{{ $attachment['name'] }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="t-chat-empty">{{ $empty }}</div>
        @endforelse
    </div>

    @isset($composer)
        <div class="t-chat-composer">{{ $composer }}</div>
    @endisset
</div>
