<?php

namespace Zenstruck\Browser\Dom\Form\Field;

use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\Panther\DomCrawler\Field\FileFormField as PantherFileFormField;
use Zenstruck\Browser\Dom\Form\Field;

/**
 * @mixin FileFormField
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileField extends Field
{
    /**
     * @param string|array $files
     */
    public function upload($files): void
    {
        $files = (array) $files;

        if (!\count($files)) {
            throw new \InvalidArgumentException('Must provide at least one file.');
        }

        foreach ($files as $file) {
            if (!\is_file($file)) {
                throw new \InvalidArgumentException("File \"{$file}\" does not exist.");
            }
        }

        if ($this->inner instanceof PantherFileFormField) {
            foreach ($files as $file) {
                $this->inner->upload($file);
            }

            return;
        }

        // Hack to allow multiple files to be attached
        $this->inner->upload(\array_shift($files));

        if (empty($files)) {
            // not multiple files
            return;
        }

        if (!$this->attr('multiple')) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file field.');
        }

        foreach ($files as $file) {
            $field = new FileFormField($this->dom->getNode(0));
            $field->upload($file);

            $this->form->set($field);
        }
    }
}
