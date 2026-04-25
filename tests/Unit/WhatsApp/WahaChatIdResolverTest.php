<?php

namespace Tests\Unit\WhatsApp;

use App\Services\WhatsApp\WahaChatIdResolver;
use Tests\TestCase;

class WahaChatIdResolverTest extends TestCase
{
    public function test_to_chat_id_keeps_c_us_identifier(): void
    {
        $resolver = new WahaChatIdResolver();

        $this->assertSame('5567999999999@c.us', $resolver->toChatId('5567999999999@c.us'));
    }

    public function test_to_chat_id_adds_c_us_when_input_is_number_only(): void
    {
        $resolver = new WahaChatIdResolver();

        $this->assertSame('5567999999999@c.us', $resolver->toChatId('55 (67) 99999-9999'));
    }

    public function test_to_chat_id_keeps_group_identifier_without_adding_c_us(): void
    {
        $resolver = new WahaChatIdResolver();

        $this->assertSame('1234567890-123456@g.us', $resolver->toChatId('1234567890-123456@g.us'));
    }

    public function test_normalize_reply_chat_id_converts_s_whatsapp_net_to_c_us(): void
    {
        $resolver = new WahaChatIdResolver();

        $this->assertSame('556793087866@c.us', $resolver->normalizeReplyChatId('556793087866@s.whatsapp.net'));
    }

    public function test_normalize_reply_chat_id_ignores_lid_identifier(): void
    {
        $resolver = new WahaChatIdResolver();

        $this->assertSame('', $resolver->normalizeReplyChatId('abc123@lid'));
    }

    public function test_normalize_reply_chat_id_strips_device_suffix_from_s_whatsapp_net_jid(): void
    {
        $resolver = new WahaChatIdResolver();

        // WAHA SenderAlt format: number:device@s.whatsapp.net — device suffix must be discarded.
        $this->assertSame('556793087866@c.us', $resolver->normalizeReplyChatId('556793087866:7@s.whatsapp.net'));
    }

    public function test_normalize_reply_chat_id_rejects_internal_lid_identifier_with_c_us_suffix(): void
    {
        $resolver = new WahaChatIdResolver();

        // WAHA sometimes emits 15-digit internal identifiers ending with @c.us that are NOT real phones.
        $this->assertSame('', $resolver->normalizeReplyChatId('215084110503978@c.us'));
    }
}
