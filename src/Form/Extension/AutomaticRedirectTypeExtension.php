<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Form\Extension;

use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException;
use Setono\SyliusRedirectPlugin\SlugUpdateHandler\SlugUpdateHandlerCommand;
use Setono\SyliusRedirectPlugin\SlugUpdateHandler\SlugUpdateHandlerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\ConstraintViolation;

abstract class AutomaticRedirectTypeExtension extends AbstractTypeExtension
{
    /** @var string */
    protected const FIELD_NAME = 'addAutomaticRedirect';

    /** @var SlugUpdateHandlerInterface */
    private $slugUpdateHandler;

    /** @var ViolationMapper */
    private $violationMapper;

    /** @var array */
    private $oldSlugs = [];

    public function __construct(SlugUpdateHandlerInterface $slugUpdateHandler)
    {
        $this->slugUpdateHandler = $slugUpdateHandler;
        $this->violationMapper = new ViolationMapper();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!$data instanceof SlugAwareInterface || !$data instanceof ResourceInterface || $data->getId() === null) {
                return;
            }

            $form->add(self::FIELD_NAME, CheckboxType::class, [
                'mapped' => false,
                'label' => 'setono_sylius_redirect.form.add_automatic_redirect',
                'required' => false,
                'attr' => [
                    'class' => 'js-add-automatic-redirection-checkbox',
                ],
            ]);

            $this->oldSlugs[self::getObjectHash($data)] = $data->getSlug();
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();

            /** @var SlugAwareInterface $data */
            $data = $event->getData();

            if (!$form->has(self::FIELD_NAME)) {
                return;
            }

            $hash = self::getObjectHash($data);
            if (!isset($this->oldSlugs[$hash])) {
                return;
            }

            $currentSlug = $this->oldSlugs[$hash];
            $newSlug = $data->getSlug();

            if (null === $currentSlug || null === $newSlug || $currentSlug === $newSlug) {
                return;
            }

            // the automatic redirect creation is not requested by the user
            if ($form->get(self::FIELD_NAME)->getData() === false) {
                return;
            }

            try {
                $this->slugUpdateHandler->handle(new SlugUpdateHandlerCommand($data, $currentSlug, $newSlug));
            } catch (SlugUpdateHandlerValidationException $e) {
                /** @var ConstraintViolation $violation */
                foreach ($e->getConstraintViolationList() as $violation) {
                    $this->violationMapper->mapViolation($violation, $form);
                }
            }
        });
    }

    private static function getObjectHash(object $obj): string
    {
        return spl_object_hash($obj);
    }
}
