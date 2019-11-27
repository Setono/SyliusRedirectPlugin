<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Form\Extension\Product;

use Setono\SyliusRedirectPlugin\Decider\AutomaticRedirectCreationDeciderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

abstract class TranslationTypeExtension extends AbstractTypeExtension
{
    /** @var AutomaticRedirectCreationDeciderInterface */
    private $automaticRedirectCreationDecider;

    public function __construct(AutomaticRedirectCreationDeciderInterface $automaticRedirectCreationDecider)
    {
        $this->automaticRedirectCreationDecider = $automaticRedirectCreationDecider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!$data instanceof SlugAwareInterface || !$data instanceof ResourceInterface || $data->getId() === null) {
                return;
            }

            $form->add('addAutomaticRedirect', CheckboxType::class, [
                'mapped' => false,
                'label' => 'setono_sylius_redirect.form.add_automatic_redirect',
                'required' => false,
                'attr' => [
                    'class' => 'js-add-automatic-redirection-checkbox',
                ],
            ]);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!$data instanceof SlugAwareInterface || !$data instanceof ResourceInterface || $data->getId() === null) {
                return;
            }
            if ($form->get('addAutomaticRedirect')->getData() === true) {
                $this->automaticRedirectCreationDecider->askAutomaticRedirectCreation($data);
            }
        });
    }
}
