<?php
/**
 * sFire Framework (https://sfire.io)
 *
 * @link      https://github.com/sfire-framework/ for the canonical source repository
 * @copyright Copyright (c) 2014-2020 sFire Framework.
 * @license   http://sfire.io/license BSD 3-CLAUSE LICENSE
 */

declare(strict_types=1);

namespace sFire\Localization;

use sFire\FileControl\File;
use sFire\DataControl\Translators\StringTranslator;


/**
 * Class Translation
 * @package sFire\Localization
 */
class Translation {


    /**
     * Contains instance of self
     * @var null|self
     */
    private static ?self $instance = null;


    /**
     * Contains all the translations
     * @var array
     */
    private array $translations = [];


    /**
     * Contains the current language (i.e. en or nl)
     * @var null|string
     */
    private ?string $language = null;


    /**
     * Returns instance of self
     * @return self
     */
    public static function getInstance(): self {

        if(null === static::$instance) {
            static::$instance = new self;
        }

        return static::$instance;
    }


    /**
     * Translates a node text value
     * @param string $path
     * @param int $plural
     * @param null|array $variables
     * @param null|string $language
     * @return string
     */
    public function translate(string $path, array $variables = null, int $plural = 0, string $language = null, string $default = null): string {

        $translations = $this -> translations[$language ?? $this -> language] ?? [];

        //Get the translations array
        $translations = $this -> getTranslation($translations, $path);

        //Find the correct translation
        if(null !== $default) {
            $content = $this -> replaceContentWithTranslations($default, $translations, $plural);
        }
        else {
            $content = $this -> replaceContentWithTranslations('', $translations, $plural);
        }

        //Replace named variables
        return $this -> replaceNamedVariables($content, $variables);
    }


    /**
     * Sets the current language
     * @param string $language
     * @return void
     */
    public function setLanguage(string $language): void {
        $this -> language = $language;
    }


    /**
     * Returns the current language
     * @return null|string
     */
    public function getLanguage(): ?string {
        return $this -> language;
    }


    /**
     * @param string $data
     * @param string $language
     * @return void
     * @throws RuntimeException
     */
    public function loadFile(string $data, string $language): void {

        $file = new File($data);

        if(false === $file -> exists()) {
            throw new RuntimeException(sprintf('Translation file "%s" does not exists', $file));
        }

        $content = $this -> parseTranslationFile($file);

        $this -> translations[$language] = array_merge($content, $this -> translations[$language] ?? []);
        $this -> language = $language;
    }


    /**
     * Returns an array with translation text that matches a given path
     * @param array $data
     * @param $path
     * @return array
     */
    private function getTranslation(array $data, $path): array {

        $translator   = new StringTranslator($data);
        $translations = $translator -> get($path);

        if(null === $translations) {
            return [];
        }

        $translations = true === is_array($translations) ? $translations : ['a' => $translations];
        return array_reverse($translations, true);
    }


    /**
     * Replaces given text content with a found translation array
     * @param string $content
     * @param array $translations
     * @param int $plural
     * @return array|string
     */
    private function replaceContentWithTranslations(string $content, array $translations, int $plural = 0) {

        $text = null;

        foreach($translations as $amount => $translation) {

            if(true === (bool) preg_match('#^(?<from>(?:-)?(?:[0-9]+))?(?<separator>,?)(?<to>(?:-)?(?:[0-9]+)?)$#', (string) $amount, $match)) {

                if($match['separator'] === '') {

                    if($plural == $match['from']) {

                        $text = $translation;
                        break;
                    }
                }
                else {

                    if($match['from'] !== '' && $match['to'] !== '') {

                        if($plural >= $match['from'] && $plural <= $match['to']) {

                            $text = $translation;
                            break;
                        }
                    }
                    elseif($match['from'] !== '' && $match['to'] === '') {

                        if($plural >= $match['from']) {

                            $text = $translation;
                            break;
                        }
                    }
                    elseif($match['from'] === '' && $match['to'] !== '') {

                        if($plural <= $match['from']) {

                            $text = $translation;
                            break;
                        }
                    }
                }
            }
            else {
                $text = $translation;
            }
        }

        return $text ?? $content;
    }


    /**
     * Replaces all named attributes in a given text
     * @param string $text
     * @param null|array $variables
     * @return null|string
     */
    private function replaceNamedVariables(string $text = null, ?array $variables): ?string {

        //Replace named variables
        if(null !== $variables) {

            foreach($variables as $replace => $replacement) {
                $text = str_replace(':' . $replace, $replacement, $text);
            }
        }

        return $text;
    }


    /**
     * @param File $file
     * @return array
     */
    private function parseTranslationFile(File $file): array {

        $content = require($file -> getPath());
        return $this -> parseTranslationArray($content);
    }


    /**
     * @param array $translations
     * @param array $output
     * @return array
     */
    private function parseTranslationArray(array $translations, &$output = []) {

        foreach($translations as $key => $translation) {

            if(true === is_array($translation)) {

                $output[$key] = [];
                $this -> parseTranslationArray($translation, $output[$key]);
                continue;
            }

            $output[$key] = $translation;
        }

        return $output;
    }
}