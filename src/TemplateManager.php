<?php

class TemplateManager
{
    // Placeholders.
    private $placeholders = [];

    // To set a defualt value if the replacement text does NOT exixts (default empty strings).
    private $emptyPlaceholdersDefaultTexts = [
        'ALL' => '',
    ];

    public function __construct()
    {
        // Return placeholders
        $this->register('quote:destination_name', function (Quote $quote) {
            return DestinationRepository::getInstance()->getById($quote->destinationId);
        });
    }

    // Register functions.
    private function register(string $placeholder, callable $fn, bool $defaultEmptyString = false)
	{
        $this->placeholders[$placeholder] = [
            'placeholder' => $placeholder,
            'defaultBlank' => $defaultEmptyString,
            'func' => function ($value) use ($fn, $defaultEmptyString) {
                try {
                    return (string) call_user_func($fn, $value);
                } catch (TypeError $e) {
                    return ($defaultEmptyString)
                        ? $this->emptyPlaceholdersDefaultTexts['ALL']
                        : $value
                    ;
                }
            },
        ];
	}

    // Match
    private function matchInterpelations(string $text, array $parameters)
	{
        preg_match_all('/\[(?<placeholder>(?<var>\w+)(:.+?)?)\]/', $text, $matches, PREG_SET_ORDER);
        $replacements = [];
        foreach ($matches as $match) {
            $default = $match[0];
            $value = $parameters[$match['var']];
            $replacements[$default] = $this->interpelate($match['placeholder'], $value);
        }
        return $replacements;
	}

    // Reflect
    private function interpelate($placeholder, $value)
	{
        return (string) call_user_func($this->placeholders[$placeholder]['func'], $value);
	}

    public function getTemplateComputed(Template $tpl, array $data)
    {
        if (!$tpl) {
            throw new \RuntimeException('no tpl given');
        }

        $replaced = clone($tpl);
        $replaced->subject = $this->computeText($replaced->subject, $data);
        $replaced->content = $this->computeText($replaced->content, $data);

        return $replaced;
    }

    private function computeText($text, array $data)
    {
        // Refactor here, call private functions to do the job...
        /*
        Define placeholders.
        Use preg_match_all for matching.
        Define interpelation functions.
        Add verifications.
        */

        /*
        $data = $this->checkParameters($text, $data);
        $interpellations = $this->matchInterpelations($text, $data);

        return strtr($text, $interpellations);

        exit;
        */

        $APPLICATION_CONTEXT = ApplicationContext::getInstance();

        $quote = (isset($data['quote']) and $data['quote'] instanceof Quote) ? $data['quote'] : null;

        if ($quote)
        {
            $_quoteFromRepository = QuoteRepository::getInstance()->getById($quote->id);
            $usefulObject = SiteRepository::getInstance()->getById($quote->siteId);
            $destinationOfQuote = DestinationRepository::getInstance()->getById($quote->destinationId);

            if(strpos($text, '[quote:destination_link]') !== false){
                $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            }

            $containsSummaryHtml = strpos($text, '[quote:summary_html]');
            $containsSummary     = strpos($text, '[quote:summary]');

            if ($containsSummaryHtml !== false || $containsSummary !== false) {
                if ($containsSummaryHtml !== false) {
                    $text = str_replace(
                        '[quote:summary_html]',
                        Quote::renderHtml($_quoteFromRepository),
                        $text
                    );
                }
                if ($containsSummary !== false) {
                    $text = str_replace(
                        '[quote:summary]',
                        Quote::renderText($_quoteFromRepository),
                        $text
                    );
                }
            }

            (strpos($text, '[quote:destination_name]') !== false) and $text = str_replace('[quote:destination_name]',$destinationOfQuote->countryName,$text);
        }

        if (isset($destination))
            $text = str_replace('[quote:destination_link]', $usefulObject->url . '/' . $destination->countryName . '/quote/' . $_quoteFromRepository->id, $text);
        else
            $text = str_replace('[quote:destination_link]', '', $text);

        /*
         * USER
         * [user:*]
         */
        $_user  = (isset($data['user'])  and ($data['user']  instanceof User))  ? $data['user']  : $APPLICATION_CONTEXT->getCurrentUser();
        if($_user) {
            (strpos($text, '[user:first_name]') !== false) and $text = str_replace('[user:first_name]'       , ucfirst(mb_strtolower($_user->firstname)), $text);
        }

        return $text;
    }
}
