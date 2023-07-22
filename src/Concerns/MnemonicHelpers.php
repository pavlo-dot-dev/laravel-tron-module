<?php

namespace PavloDotDev\LaravelTronModule\Concerns;

use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\WordList;

trait MnemonicHelpers
{
    /**
     * Генерация мнемонической фразы.
     */
    public function generateMnemonic(int $wordCount = 15, WordList|string $lang = 'english'): \FurqanSiddiqui\BIP39\Mnemonic
    {
        return BIP39::Generate($wordCount, $lang);
    }

    /**
     * Валидация мнемонической фразы.
     */
    public function validateMnemonic(string|array $words): bool
    {
        try {
            BIP39::Words($words);
        } catch (\Exception) {
            return false;
        }

        return true;
    }
}