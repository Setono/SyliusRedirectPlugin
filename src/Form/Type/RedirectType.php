<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class RedirectType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('source', TextType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.source',
            ])
            ->add('destination', TextType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.destination',
            ])
            ->add('permanent', CheckboxType::class, [
                'label' => 'setono_sylius_redirect.form.redirect.permanent',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'setono_sylius_redirect_redirect';
    }
}
