<?php

namespace Botflow\Contracts;

enum FlowBed: string
{
    case COMMAND = 'command';

    case MESSAGE = 'message';

    case MESSAGE_UPDATE = 'message_update';

    case CHANNEL_POST = 'channel_post';

    case CHANNEL_POST_UPDATE = 'channel_post_update';

    case CALLBACK_QUERY = 'callback_query';

    case INLINE_QUERY = 'inline_query';

    case CHAT_MEMBERS_JOINED = 'chat_members_joined';

    case CHAT_MEMBERS_LEFT = 'chat_members_left';
}
