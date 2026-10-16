<?php

declare(strict_types=1);

namespace Lumi\Laravel\Tests;

use Lumi\Laravel\Notifications\Messages\LumiMessage;
use PHPUnit\Framework\TestCase;

class LumiMessageTest extends TestCase
{
    public function testMessageFluentBuilder(): void
    {
        $msg = LumiMessage::create('Important Account Update')
            ->from('notifications@mailifica.com')
            ->to('user@example.com')
            ->html('<p>Your account has been updated.</p>')
            ->text('Your account has been updated.')
            ->replyTo('support@mailifica.com')
            ->cc(['billing@example.com'])
            ->tag('env', 'production')
            ->metadata('user_id', 99)
            ->template('tmpl_welcome_01', ['name' => 'Tarcisio']);

        $array = $msg->toArray();

        $this->assertEquals('notifications@mailifica.com', $array['from']);
        $this->assertEquals('user@example.com', $array['to']);
        $this->assertEquals('Important Account Update', $array['subject']);
        $this->assertEquals('<p>Your account has been updated.</p>', $array['html']);
        $this->assertEquals('tmpl_welcome_01', $array['template_id']);
        $this->assertEquals(['name' => 'Tarcisio'], $array['variables']);
        $this->assertEquals([['name' => 'env', 'value' => 'production']], $array['tags']);
        $this->assertEquals(['user_id' => 99], $array['metadata']);
    }
}
