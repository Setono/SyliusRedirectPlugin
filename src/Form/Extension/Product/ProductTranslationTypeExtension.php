<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\Form\Extension\Product;

use Sylius\Bundle\ProductBundle\Form\Type\ProductTranslationType;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class ProductTranslationTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): array
    {
        return [ProductTranslationType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!$data instanceof ProductTranslationInterface || $data->getId() === null) {
                return;
            }

            $form->add('addAutomaticRedirection', CheckboxType::class, [
                'mapped' => false,
                'label' => 'setono_sylius_redirect.form.product.add_automatic_redirection',
                'required' => false,
                'attr' => [
                    'class' => 'js-add-automatic-redirection-checkbox',
                ],
            ]);
        });
    }

    public function getExtendedType(): string
    {
        return ProductTranslationType::class;
    }
}
