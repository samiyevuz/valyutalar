<?php

namespace App\Builders\Keyboard;

class KeyboardBuilder
{
    protected array $keyboard = [];
    protected bool $inline = false;
    protected bool $resizeKeyboard = true;
    protected bool $oneTimeKeyboard = false;
    protected bool $selective = false;

    public static function inline(): self
    {
        $builder = new self();
        $builder->inline = true;
        return $builder;
    }

    public static function reply(): self
    {
        return new self();
    }

    public function row(): self
    {
        $this->keyboard[] = [];
        return $this;
    }

    public function button(string $text, ?string $callbackData = null, ?string $url = null): self
    {
        if (empty($this->keyboard)) {
            $this->row();
        }

        $button = ['text' => $text];

        if ($this->inline) {
            if ($callbackData) {
                $button['callback_data'] = $callbackData;
            }
            if ($url) {
                $button['url'] = $url;
            }
        }

        $lastRow = count($this->keyboard) - 1;
        $this->keyboard[$lastRow][] = $button;

        return $this;
    }

    public function buttons(array $buttons): self
    {
        foreach ($buttons as $button) {
            if (is_array($button)) {
                $this->button(
                    $button['text'],
                    $button['callback_data'] ?? null,
                    $button['url'] ?? null
                );
            } else {
                $this->button($button);
            }
        }

        return $this;
    }

    public function resize(bool $resize = true): self
    {
        $this->resizeKeyboard = $resize;
        return $this;
    }

    public function oneTime(bool $oneTime = true): self
    {
        $this->oneTimeKeyboard = $oneTime;
        return $this;
    }

    public function selective(bool $selective = true): self
    {
        $this->selective = $selective;
        return $this;
    }

    public function build(): array
    {
        if ($this->inline) {
            return ['inline_keyboard' => $this->keyboard];
        }

        return [
            'keyboard' => $this->keyboard,
            'resize_keyboard' => $this->resizeKeyboard,
            'one_time_keyboard' => $this->oneTimeKeyboard,
            'selective' => $this->selective,
        ];
    }

    public static function removeKeyboard(): array
    {
        return ['remove_keyboard' => true];
    }

    public static function forceReply(bool $selective = false): array
    {
        return [
            'force_reply' => true,
            'selective' => $selective,
        ];
    }
}

