<?php

/*
 * This file is part of Monsieur Biz' Media Manager plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusMediaManagerPlugin\Tests\Unit\Form\Type;

use MonsieurBiz\SyliusMediaManagerPlugin\Form\Type\UploadType;
use MonsieurBiz\SyliusMediaManagerPlugin\Provider\MimeTypesProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

final class UploadTypeTest extends TestCase
{
    public function testItConfiguresTheFileConstraints(): void
    {
        $form = $this->createUploadForm('image');

        $constraints = $form->get('file')->getConfig()->getOption('constraints');
        self::assertCount(2, $constraints);

        [$notBlank, $file] = $constraints;
        self::assertInstanceOf(NotBlank::class, $notBlank);
        self::assertSame('monsieurbiz_sylius_media_manager.error.cannot_upload_file', $notBlank->message);

        self::assertInstanceOf(File::class, $file);
        self::assertSame(5000000, $file->maxSize);
        self::assertSame(MimeTypesProviderInterface::IMAGE_TYPE_MIMES, $file->mimeTypes);
        self::assertSame('monsieurbiz_sylius_media_manager.error.max_file_size', $file->uploadIniSizeErrorMessage);
        self::assertSame('monsieurbiz_sylius_media_manager.error.invalid_mime_type.image', $file->mimeTypesMessage);
    }

    public function testItDoesNotConfigureConstraintsWithAnArrayOfOptions(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $this->createUploadForm('image');
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $deprecations);
    }

    private function createUploadForm(string $type): FormInterface
    {
        $mimeTypesProvider = $this->createMock(MimeTypesProviderInterface::class);
        $mimeTypesProvider->method('getMimeTypesByType')->willReturnMap([
            [$type, MimeTypesProviderInterface::IMAGE_TYPE_MIMES],
        ]);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new UploadType('2M', [$type => '5M'], $mimeTypesProvider))
            ->getFormFactory()
            ->create(UploadType::class, null, ['type' => $type])
        ;
    }
}
