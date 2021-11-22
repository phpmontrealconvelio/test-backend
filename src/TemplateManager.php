<?php

class TemplateManager
{
    // Placeholders.
    private $placeholders = [];

    // To set a defualt value if the replacement text does NOT exixts (default empty strings).
    private $emptyPlaceholdersDefaultTexts = [
        'quote:destination_link' => '',
        'user:first_name' => '',
        'ALL' => '',
    ];

    public function __construct()
    {
        // Register placeholders.
        $this->register('quote:destination_name', function (Quote $quote) {
            $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            return $destination->countryName;
        });
        $this->register('quote:destination_link', function (Quote $quote) {
            $destination = DestinationRepository::getInstance()->getById($quote->destinationId);
            $site = SiteRepository::getInstance()->getById($quote->siteId);
            return ($destination)
                ? $site->url . '/' . $destination->countryName . '/quote/' . $quote->id
                : $this->emptyPlaceholdersDefaultTexts['quote:destination_link']
            ;
        });
        $this->register('quote:summary_html', function (Quote $quote) {
            return Quote::renderHtml($quote);
        });
        $this->register('quote:summary', function (Quote $quote) {
            return Quote::renderText($quote);
        });
        $this->register('user:first_name', function (User $user) {
            $user = ApplicationContext::getInstance()->getCurrentUser();
            return ($user)
                ? $user->firstname
                : $this->emptyPlaceholdersDefaultTexts['user:first_name']
            ;
        });
    }

    // Register functions.
    private function register(string $placeholder, callable $fn, bool $defaultEmptyString = false)
	{
        $this->checkReflectedFuntionsParams($fn);
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
        if (array_key_exists($placeholder, $this->placeholders)) {
            return (string) call_user_func($this->placeholders[$placeholder]['func'], $value);
        } else {
            return $value;
        }
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

    private function checkReflectedFuntionsParams(callable $fn)
	{
        $reflectedFn = new \ReflectionFunction($fn);
        if (! $reflectedFn) {
            throw new \Exception("The function is not implemented");
        }
        if ($reflectedFn->getNumberOfParameters() != 1) {
            throw new \Exception("Expected at least 1 argument");
        }
        $reflectedParams = $reflectedFn->getParameters();
        if ($reflectedParams[0]->getType()->allowsNull() === true) {
            throw new \Exception("Null value is not allowed");
        }
        if ($reflectedParams[0]->isOptional() === true) {
            throw new \Exception("Argument 1 Cannot be optional");
        }
	}

    private function computeText($text, array $data)
	{
        $data = $this->checkParameters($text, $data);
        $interpellations = $this->matchInterpelations($text, $data);

        return strtr($text, $interpellations);
	}

    private function checkParameters(string $text, array $parameters): array
	{
        preg_match_all('/\[(?<vars>\w+)(:.+?)?\]/', $text, $matches);
        $expectedKeys = $matches['vars'];
        $defaults = array_fill_keys($expectedKeys, null);
        $parameters = array_merge($defaults, $parameters);
        $parameters = array_intersect_key($parameters, $defaults);

        if (in_array('quote', $expectedKeys)) {
            $parameters['quote'] = ($parameters['quote'] instanceof Quote)
                ? $parameters['quote']
                : null
            ;
        }
        if (in_array('user', $expectedKeys)) {
            $parameters['user'] = ($parameters['user'] instanceof User)
                ? $parameters['user']
                : ApplicationContext::getInstance()->getCurrentUser()
            ;
        }

        return $parameters;
	}

}
