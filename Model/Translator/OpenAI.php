<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Translator;

use Exception;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use OpenAI as OpenAITranslator;
use OpenAI\Client as OpenAIClient;
use RuntimeException;

class OpenAI implements TranslatorInterface
{
    protected ?OpenAIClient $translator = null;

    /**
     * @param OpenAITranslator $openAITranslator
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        protected OpenAITranslator $openAITranslator,
        protected ModuleConfig $moduleConfig
    ) {
    }

    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throws Exception
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        $this->translator ??= $this->openAITranslator::client(
            $this->moduleConfig->getOpenAIApiKey(),
            $this->moduleConfig->getOpenAIOrgID(),
            $this->moduleConfig->getOpenAIProjectID() ?: null
        );

        $sourceFragment = $sourceLang ? ' from ' . $sourceLang : '';
        $prompt = 'Translate this text, with the context that this text is used in an e-commerce store as part of a'
            . ' product description or a category description without asking any further questions or'
            . ' clarifications, giving only the answer and nothing else,'
            . $sourceFragment . ' to ' . $targetLang . '.';

        $result = $this->translator->chat()->create([
            'model' => $this->moduleConfig->getOpenAIModel(),
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $text],
            ],
        ])->toArray();

        $content = $result['choices'][0]['message']['content'];
        if ($content === null) {
            throw new RuntimeException((string)__(
                'OpenAI returned an empty translation for model "%1".',
                $this->moduleConfig->getOpenAIModel()
            ));
        }

        return trim($content);
    }
}
