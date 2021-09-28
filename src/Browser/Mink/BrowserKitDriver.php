<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Mink;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\Exception\BadMethodCallException;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Field\FormField;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\DomCrawler\Field\TextareaFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * Copied from https://github.com/minkphp/MinkBrowserKitDriver for use
 * until it supports Symfony 5/PHP 8.
 *
 * @ref https://github.com/minkphp/MinkBrowserKitDriver
 * @ref https://github.com/minkphp/MinkBrowserKitDriver/pull/151
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @internal
 */
final class BrowserKitDriver extends CoreDriver
{
    private $client;

    /**
     * @var Form[]
     */
    private $forms = [];
    private $serverParameters = [];
    private $started = false;
    private $removeScriptFromUrl = false;
    private $removeHostFromUrl = false;

    /**
     * Initializes BrowserKit driver.
     *
     * @param AbstractBrowser $client  BrowserKit client instance
     * @param string|null     $baseUrl Base URL for HttpKernel clients
     */
    public function __construct(AbstractBrowser $client, $baseUrl = null)
    {
        $this->client = $client;
        $this->client->followRedirects(true);

        if (null !== $baseUrl && $client instanceof HttpKernelBrowser) {
            $client->setServerParameter('SCRIPT_FILENAME', \parse_url($baseUrl, \PHP_URL_PATH));
        }
    }

    /**
     * Returns BrowserKit HTTP client instance.
     *
     * @return AbstractBrowser
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Tells driver to remove hostname from URL.
     *
     * @param bool $remove
     *
     * @deprecated Deprecated as of 1.2, to be removed in 2.0. Pass the base url in the constructor instead.
     */
    public function setRemoveHostFromUrl($remove = true)
    {
        @\trigger_error(
            'setRemoveHostFromUrl() is deprecated as of 1.2 and will be removed in 2.0. Pass the base url in the constructor instead.',
            \E_USER_DEPRECATED
        );
        $this->removeHostFromUrl = (bool) $remove;
    }

    /**
     * Tells driver to remove script name from URL.
     *
     * @param bool $remove
     *
     * @deprecated Deprecated as of 1.2, to be removed in 2.0. Pass the base url in the constructor instead.
     */
    public function setRemoveScriptFromUrl($remove = true)
    {
        @\trigger_error(
            'setRemoveScriptFromUrl() is deprecated as of 1.2 and will be removed in 2.0. Pass the base url in the constructor instead.',
            \E_USER_DEPRECATED
        );
        $this->removeScriptFromUrl = (bool) $remove;
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function stop(): void
    {
        $this->reset();
        $this->started = false;
    }

    public function reset(): void
    {
        // Restarting the client resets the cookies and the history
        $this->client->restart();
        $this->forms = [];
        $this->serverParameters = [];
    }

    public function visit($url): void
    {
        $this->client->request('GET', $this->prepareUrl($url), [], [], $this->serverParameters);
        $this->forms = [];
    }

    public function getCurrentUrl(): string
    {
        // This should be encapsulated in `getRequest` method if any other method needs the request
        try {
            $request = $this->client->getInternalRequest();
        } catch (BadMethodCallException $e) {
            // Handling Symfony 5+ behaviour
            $request = null;
        }

        if (null === $request) {
            throw new DriverException('Unable to access the request before visiting a page');
        }

        return $request->getUri();
    }

    public function reload(): void
    {
        $this->client->reload();
        $this->forms = [];
    }

    public function forward(): void
    {
        $this->client->forward();
        $this->forms = [];
    }

    public function back(): void
    {
        $this->client->back();
        $this->forms = [];
    }

    public function setBasicAuth($user, $password): void
    {
        if (false === $user) {
            unset($this->serverParameters['PHP_AUTH_USER'], $this->serverParameters['PHP_AUTH_PW']);

            return;
        }

        $this->serverParameters['PHP_AUTH_USER'] = $user;
        $this->serverParameters['PHP_AUTH_PW'] = $password;
    }

    public function setRequestHeader($name, $value): void
    {
        $contentHeaders = ['CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true];
        $name = \str_replace('-', '_', \mb_strtoupper($name));

        // CONTENT_* are not prefixed with HTTP_ in PHP when building $_SERVER
        if (!isset($contentHeaders[$name])) {
            $name = 'HTTP_'.$name;
        }

        $this->serverParameters[$name] = $value;
    }

    public function getResponseHeaders(): array
    {
        return $this->getResponse()->getHeaders();
    }

    public function setCookie($name, $value = null): void
    {
        if (null === $value) {
            $this->deleteCookie($name);

            return;
        }

        $jar = $this->client->getCookieJar();
        $jar->set(new Cookie($name, $value));
    }

    public function getCookie($name): ?string
    {
        // Note that the following doesn't work well because
        // Symfony\Component\BrowserKit\CookieJar stores cookies by name,
        // path, AND domain and if you don't fill them all in correctly then
        // you won't get the value that you're expecting.
        //
        // $jar = $this->client->getCookieJar();
        //
        // if (null !== $cookie = $jar->get($name)) {
        //     return $cookie->getValue();
        // }

        $allValues = $this->client->getCookieJar()->allValues($this->getCurrentUrl());

        if (isset($allValues[$name])) {
            return $allValues[$name];
        }

        return null;
    }

    public function getStatusCode(): int
    {
        $response = $this->getResponse();

        // BC layer for Symfony < 4.3
        if (!\method_exists($response, 'getStatusCode')) {
            return $response->getStatus();
        }

        return $response->getStatusCode();
    }

    public function getContent(): string
    {
        return $this->getResponse()->getContent();
    }

    public function findElementXpaths($xpath): array
    {
        $nodes = $this->getCrawler()->filterXPath($xpath);

        $elements = [];
        foreach ($nodes as $i => $node) {
            $elements[] = \sprintf('(%s)[%d]', $xpath, $i + 1);
        }

        return $elements;
    }

    public function getTagName($xpath): string
    {
        return $this->getCrawlerNode($this->getFilteredCrawler($xpath))->nodeName;
    }

    public function getText($xpath): string
    {
        $text = $this->getFilteredCrawler($xpath)->text(null, true);
        // TODO drop our own normalization once supporting only dom-crawler 4.4+ as it already does it.
        $text = \str_replace("\n", ' ', $text);
        $text = \preg_replace('/ {2,}/', ' ', $text);

        return \trim($text);
    }

    public function getHtml($xpath): string
    {
        return $this->getFilteredCrawler($xpath)->html();
    }

    public function getOuterHtml($xpath): string
    {
        $crawler = $this->getFilteredCrawler($xpath);

        if (\method_exists($crawler, 'outerHtml')) {
            return $crawler->outerHtml();
        }

        $node = $this->getCrawlerNode($crawler);

        return $node->ownerDocument->saveHTML($node);
    }

    public function getAttribute($xpath, $name): ?string
    {
        $node = $this->getFilteredCrawler($xpath);

        if ($this->getCrawlerNode($node)->hasAttribute($name)) {
            return $node->attr($name);
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getValue($xpath)
    {
        if (\in_array($this->getAttribute($xpath, 'type'), ['submit', 'image', 'button'], true)) {
            return $this->getAttribute($xpath, 'value');
        }

        $node = $this->getCrawlerNode($this->getFilteredCrawler($xpath));

        if ('option' === $node->tagName) {
            return $this->getOptionValue($node);
        }

        try {
            $field = $this->getFormField($xpath);
        } catch (\InvalidArgumentException $e) {
            return $this->getAttribute($xpath, 'value');
        }

        $value = $field->getValue();

        if ('select' === $node->tagName && null === $value) {
            // symfony/dom-crawler returns null as value for a non-multiple select without
            // options but we want an empty string to match browsers.
            $value = '';
        }

        return $value;
    }

    public function setValue($xpath, $value): void
    {
        $this->getFormField($xpath)->setValue($value);
    }

    public function check($xpath): void
    {
        $this->getCheckboxField($xpath)->tick();
    }

    public function uncheck($xpath): void
    {
        $this->getCheckboxField($xpath)->untick();
    }

    public function selectOption($xpath, $value, $multiple = false): void
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof ChoiceFormField) {
            throw new DriverException(\sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
        }

        if ($multiple) {
            $oldValue = (array) $field->getValue();
            $oldValue[] = $value;
            $value = $oldValue;
        }

        $field->select($value);
    }

    public function isSelected($xpath): bool
    {
        $optionValue = $this->getOptionValue($this->getCrawlerNode($this->getFilteredCrawler($xpath)));
        $selectField = $this->getFormField('('.$xpath.')/ancestor-or-self::*[local-name()="select"]');
        $selectValue = $selectField->getValue();

        return \is_array($selectValue) ? \in_array($optionValue, $selectValue, true) : $optionValue === $selectValue;
    }

    public function click($xpath): void
    {
        $crawler = $this->getFilteredCrawler($xpath);
        $node = $this->getCrawlerNode($crawler);
        $tagName = $node->nodeName;

        if ('a' === $tagName) {
            $this->client->click($crawler->link());
            $this->forms = [];
        } elseif ($this->canSubmitForm($node)) {
            $this->submit($crawler->form());
        } elseif ($this->canResetForm($node)) {
            $this->resetForm($node);
        } else {
            $message = \sprintf('%%s supports clicking on links and submit or reset buttons only. But "%s" provided', $tagName);

            throw new UnsupportedDriverActionException($message, $this);
        }
    }

    public function isChecked($xpath): bool
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof ChoiceFormField || 'select' === $field->getType()) {
            throw new DriverException(\sprintf('Impossible to get the checked state of the element with XPath "%s" as it is not a checkbox or radio input', $xpath));
        }

        if ('checkbox' === $field->getType()) {
            return $field->hasValue();
        }

        $radio = $this->getCrawlerNode($this->getFilteredCrawler($xpath));

        return $radio->getAttribute('value') === $field->getValue();
    }

    public function attachFile($xpath, $path): void
    {
        $files = (array) $path;
        $field = $this->getFormField($xpath);

        if (!$field instanceof FileFormField) {
            throw new DriverException(\sprintf('Impossible to attach a file on the element with XPath "%s" as it is not a file input', $xpath));
        }

        $field->upload(\array_shift($files));

        if (empty($files)) {
            // not multiple files
            return;
        }

        $node = $this->getFilteredCrawler($xpath);

        if (null === $node->attr('multiple')) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file field.');
        }

        $fieldNode = $this->getCrawlerNode($this->getFilteredCrawler($xpath));
        $form = $this->getFormForFieldNode($fieldNode);

        foreach ($files as $file) {
            $field = new FileFormField($fieldNode);
            $field->upload($file);

            $form->set($field);
        }
    }

    public function submitForm($xpath): void
    {
        $crawler = $this->getFilteredCrawler($xpath);

        $this->submit($crawler->form());
    }

    /**
     * @return Response
     *
     * @throws DriverException If there is not response yet
     */
    protected function getResponse()
    {
        try {
            $response = $this->client->getInternalResponse();
        } catch (BadMethodCallException $e) {
            // Handling Symfony 5+ behaviour
            $response = null;
        }

        if (null === $response) {
            throw new DriverException('Unable to access the response before visiting a page');
        }

        return $response;
    }

    /**
     * Prepares URL for visiting.
     * Removes "*.php/" from urls and then passes it to BrowserKitDriver::visit().
     *
     * @param string $url
     *
     * @return string
     */
    protected function prepareUrl($url)
    {
        if (!$this->removeHostFromUrl && !$this->removeScriptFromUrl) {
            return $url;
        }

        $replacement = ($this->removeHostFromUrl ? '' : '$1').($this->removeScriptFromUrl ? '' : '$2');

        return \preg_replace('#(https?\://[^/]+)(/[^/\.]+\.php)?#', $replacement, $url);
    }

    /**
     * Returns form field from XPath query.
     *
     * @param string $xpath
     *
     * @return FormField
     *
     * @throws DriverException
     */
    protected function getFormField($xpath)
    {
        $fieldNode = $this->getCrawlerNode($this->getFilteredCrawler($xpath));
        $fieldName = \str_replace('[]', '', $fieldNode->getAttribute('name'));

        $form = $this->getFormForFieldNode($fieldNode);

        if (\is_array($form[$fieldName])) {
            return $form[$fieldName][$this->getFieldPosition($fieldNode)];
        }

        return $form[$fieldName];
    }

    private function getFormForFieldNode(\DOMElement $fieldNode): Form
    {
        $formNode = $this->getFormNode($fieldNode);
        $formId = $this->getFormNodeId($formNode);

        if (!isset($this->forms[$formId])) {
            $this->forms[$formId] = new Form($formNode, $this->getCurrentUrl());
        }

        return $this->forms[$formId];
    }

    /**
     * Deletes a cookie by name.
     *
     * @param string $name cookie name
     */
    private function deleteCookie($name)
    {
        $path = $this->getCookiePath();
        $jar = $this->client->getCookieJar();

        do {
            if (null !== $jar->get($name, $path)) {
                $jar->expire($name, $path);
            }

            $path = \preg_replace('/.$/', '', $path);
        } while ($path);
    }

    /**
     * Returns current cookie path.
     *
     * @return string
     */
    private function getCookiePath()
    {
        $path = \dirname(\parse_url($this->getCurrentUrl(), \PHP_URL_PATH));

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $path = \str_replace('\\', '/', $path);
        }

        return $path;
    }

    /**
     * Returns the checkbox field from xpath query, ensuring it is valid.
     *
     * @param string $xpath
     *
     * @return ChoiceFormField
     *
     * @throws DriverException when the field is not a checkbox
     */
    private function getCheckboxField($xpath)
    {
        $field = $this->getFormField($xpath);

        if (!$field instanceof ChoiceFormField) {
            throw new DriverException(\sprintf('Impossible to check the element with XPath "%s" as it is not a checkbox', $xpath));
        }

        return $field;
    }

    /**
     * @return \DOMElement
     *
     * @throws DriverException if the form node cannot be found
     */
    private function getFormNode(\DOMElement $element)
    {
        if ($element->hasAttribute('form')) {
            $formId = $element->getAttribute('form');
            $formNode = $element->ownerDocument->getElementById($formId);

            if (null === $formNode || 'form' !== $formNode->nodeName) {
                throw new DriverException(\sprintf('The selected node has an invalid form attribute (%s).', $formId));
            }

            return $formNode;
        }

        $formNode = $element;

        do {
            // use the ancestor form element
            if (null === $formNode = $formNode->parentNode) {
                throw new DriverException('The selected node does not have a form ancestor.');
            }
        } while ('form' !== $formNode->nodeName);

        return $formNode;
    }

    /**
     * Gets the position of the field node among elements with the same name.
     *
     * BrowserKit uses the field name as index to find the field in its Form object.
     * When multiple fields have the same name (checkboxes for instance), it will return
     * an array of elements in the order they appear in the DOM.
     *
     * @return int
     */
    private function getFieldPosition(\DOMElement $fieldNode)
    {
        $elements = $this->getCrawler()->filterXPath('//*[@name=\''.$fieldNode->getAttribute('name').'\']');

        if (\count($elements) > 1) {
            // more than one element contains this name !
            // so we need to find the position of $fieldNode
            foreach ($elements as $key => $element) {
                /** @var \DOMElement $element */
                if ($element->getNodePath() === $fieldNode->getNodePath()) {
                    return $key;
                }
            }
        }

        return 0;
    }

    private function submit(Form $form)
    {
        $formId = $this->getFormNodeId($form->getFormNode());

        if (isset($this->forms[$formId])) {
            $form = $this->addButtons($form, $this->forms[$formId]);
        }

        // remove empty file fields from request
        foreach ($form->getFiles() as $name => $field) {
            if (empty($field['name']) && empty($field['tmp_name'])) {
                $form->remove($name);
            }
        }

        foreach ($form->all() as $field) {
            // Add a fix for https://github.com/symfony/symfony/pull/10733 to support Symfony versions which are not fixed
            if ($field instanceof TextareaFormField && null === $field->getValue()) {
                $field->setValue('');
            }
        }

        $this->client->submit($form, [], $this->serverParameters);

        $this->forms = [];
    }

    private function resetForm(\DOMElement $fieldNode)
    {
        $formNode = $this->getFormNode($fieldNode);
        $formId = $this->getFormNodeId($formNode);
        unset($this->forms[$formId]);
    }

    /**
     * Determines if a node can submit a form.
     *
     * @param \DOMElement $node node
     *
     * @return bool
     */
    private function canSubmitForm(\DOMElement $node)
    {
        $type = $node->hasAttribute('type') ? $node->getAttribute('type') : null;

        if ('input' === $node->nodeName && \in_array($type, ['submit', 'image'], true)) {
            return true;
        }

        return 'button' === $node->nodeName && (null === $type || 'submit' === $type);
    }

    /**
     * Determines if a node can reset a form.
     *
     * @param \DOMElement $node node
     *
     * @return bool
     */
    private function canResetForm(\DOMElement $node)
    {
        $type = $node->hasAttribute('type') ? $node->getAttribute('type') : null;

        return \in_array($node->nodeName, ['input', 'button'], true) && 'reset' === $type;
    }

    /**
     * Returns form node unique identifier.
     *
     * @return string
     */
    private function getFormNodeId(\DOMElement $form)
    {
        return \md5($form->getLineNo().$form->getNodePath().$form->nodeValue);
    }

    /**
     * Gets the value of an option element.
     *
     * @return string
     *
     * @see \Symfony\Component\DomCrawler\Field\ChoiceFormField::buildOptionValue
     */
    private function getOptionValue(\DOMElement $option)
    {
        if ($option->hasAttribute('value')) {
            return $option->getAttribute('value');
        }

        if (!empty($option->nodeValue)) {
            return $option->nodeValue;
        }

        return '1'; // DomCrawler uses 1 by default if there is no text in the option
    }

    /**
     * Returns DOMElement from crawler instance.
     *
     * @return \DOMElement
     *
     * @throws DriverException when the node does not exist
     */
    private function getCrawlerNode(Crawler $crawler)
    {
        $node = null;

        if ($crawler instanceof \Iterator) {
            // for symfony 2.3 compatibility as getNode is not public before symfony 2.4
            $crawler->rewind();
            $node = $crawler->current();
        } else {
            $node = $crawler->getNode(0);
        }

        if (null !== $node) {
            return $node;
        }

        throw new DriverException('The element does not exist');
    }

    /**
     * Returns a crawler filtered for the given XPath, requiring at least 1 result.
     *
     * @param string $xpath
     *
     * @return Crawler
     *
     * @throws DriverException when no matching elements are found
     */
    private function getFilteredCrawler($xpath)
    {
        if (!\count($crawler = $this->getCrawler()->filterXPath($xpath))) {
            throw new DriverException(\sprintf('There is no element matching XPath "%s"', $xpath));
        }

        return $crawler;
    }

    /**
     * Returns crawler instance (got from client).
     *
     * @return Crawler
     *
     * @throws DriverException
     */
    private function getCrawler()
    {
        $crawler = $this->client->getCrawler();

        if (null === $crawler) {
            throw new DriverException('Unable to access the response content before visiting a page');
        }

        return $crawler;
    }

    /**
     * Adds button fields from submitted form to cached version.
     */
    private function addButtons(Form $submitted, Form $cached): Form
    {
        foreach ($submitted->all() as $field) {
            if (!$field instanceof InputFormField) {
                continue;
            }

            $nodeReflection = (new \ReflectionObject($field))->getProperty('node');
            $nodeReflection->setAccessible(true);

            $node = $nodeReflection->getValue($field);

            if ('button' === $node->nodeName || \in_array($node->getAttribute('type'), ['submit', 'button', 'image'])) {
                $cached->set($field);
            }
        }

        return $cached;
    }
}
