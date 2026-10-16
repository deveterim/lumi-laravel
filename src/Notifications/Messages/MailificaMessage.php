<?php

declare(strict_types=1);

namespace Lumi\Laravel\Notifications\Messages;

class LumiMessage
{
    public ?string $from = null;
    public ?string $to = null;
    public ?string $subject = null;
    public ?string $html = null;
    public ?string $text = null;
    public ?string $replyTo = null;
    public array $cc = [];
    public array $bcc = [];
    public array $attachments = [];
    public array $tags = [];
    public array $metadata = [];
    public array $headers = [];
    public ?string $templateId = null;
    public array $variables = [];
    public ?string $scheduledAt = null;

    public static function create(?string $subject = null): self
    {
        $message = new self();
        if ($subject !== null) {
            $message->subject($subject);
        }
        return $message;
    }

    public function from(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function to(string $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function replyTo(string $replyTo): self
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function cc(string|array $cc): self
    {
        $this->cc = is_array($cc) ? $cc : [$cc];
        return $this;
    }

    public function bcc(string|array $bcc): self
    {
        $this->bcc = is_array($bcc) ? $bcc : [$bcc];
        return $this;
    }

    public function template(string $templateId, array $variables = []): self
    {
        $this->templateId = $templateId;
        $this->variables = $variables;
        return $this;
    }

    public function variables(array $variables): self
    {
        $this->variables = array_merge($this->variables, $variables);
        return $this;
    }

    public function tag(string $name, string $value = 'true'): self
    {
        $this->tags[] = ['name' => $name, 'value' => $value];
        return $this;
    }

    public function metadata(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->metadata = array_merge($this->metadata, $key);
        } else {
            $this->metadata[$key] = $value;
        }
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param string|resource $content
     */
    public function attach(string $filename, mixed $content, ?string $contentType = null): self
    {
        $body = is_resource($content) ? stream_get_contents($content) : $content;
        $this->attachments[] = [
            'filename' => $filename,
            'content' => base64_encode($body),
            'content_type' => $contentType,
        ];
        return $this;
    }

    public function attachFromPath(string $filePath, ?string $filename = null, ?string $contentType = null): self
    {
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $filename = $filename ?: basename($filePath);
            return $this->attach($filename, $content, $contentType);
        }
        return $this;
    }

    public function scheduledAt(string $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function toArray(): array
    {
        $payload = array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'subject' => $this->subject,
            'html' => $this->html,
            'text' => $this->text,
            'reply_to' => $this->replyTo,
            'cc' => !empty($this->cc) ? $this->cc : null,
            'bcc' => !empty($this->bcc) ? $this->bcc : null,
            'attachments' => !empty($this->attachments) ? $this->attachments : null,
            'tags' => !empty($this->tags) ? $this->tags : null,
            'metadata' => !empty($this->metadata) ? $this->metadata : null,
            'headers' => !empty($this->headers) ? $this->headers : null,
            'template_id' => $this->templateId,
            'variables' => !empty($this->variables) ? $this->variables : null,
            'scheduled_at' => $this->scheduledAt,
        ], fn($val) => $val !== null);

        return $payload;
    }
}
