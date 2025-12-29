<?php

namespace App\Form;

use App\Entity\Season;
use App\Entity\Show;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('anilistId')
            ->add('seasons', EntityType::class, [
                'class' => Season::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.rankOrder', 'ASC');
                },
                'choice_label' => function(Season $season) {
                    $label = $season->getName();
                    if ($season->hasActiveElection()) {
                        $label .= ' (active election)';
                    }
                    return $label;
                },
                'choice_attr' => function(Season $season) {
                    // Disable seasons with active elections
                    return $season->hasActiveElection() ? ['disabled' => 'disabled'] : [];
                },
                'expanded' => true,
                'multiple'=> true,
                'required' => false,
            ])
            // Note: relatedShows field will be added in PRE_SUBMIT event listener
            // to handle dynamic validation of Select2-chosen shows
            ->add('excludeFromElections', CheckboxType::class, [
                'required' => false,
                'label' => 'Exclude from elections'
            ])
        ;

        // PRE_SET_DATA: Add relatedShows field on initial load
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $show = $event->getData();
            $form = $event->getForm();

            // Get currently selected related show IDs
            $selectedIds = [];
            if ($show && $show->getRelatedShows()) {
                foreach ($show->getRelatedShows() as $relatedShow) {
                    $selectedIds[] = $relatedShow->getId();
                }
            }

            // Add relatedShows field with query for pre-selected shows
            $form->add('relatedShows', EntityType::class, [
                'class' => Show::class,
                'choice_label' => function(Show $show) {
                    return $show->getTitlesForSelect();
                },
                'query_builder' => function (EntityRepository $er) use ($selectedIds) {
                    $qb = $er->createQueryBuilder('sh')
                        ->orderBy('sh.japaneseTitle', 'ASC');

                    if (!empty($selectedIds)) {
                        $qb->where('sh.id IN (:selectedIds)')
                            ->setParameter('selectedIds', $selectedIds);
                    } else {
                        // Return empty result set if nothing selected
                        $qb->where('1 = 0');
                    }

                    return $qb;
                },
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);

            if ($show !== null && $show->getId() !== null) {
                $form->add('japaneseTitle')
                    ->add('fullJapaneseTitle')
                    ->add('englishTitle')
                    ->add('coverImageMedium')
                    ->add('coverImageLarge')
                    ->add('siteUrl')
                    ->add('malId')
                    ->add('updateFromAnilist', SubmitType::class, ['label' => 'Update from Anilist'])
                    ;

            }
        });

        // PRE_SUBMIT: Re-add relatedShows field with query that includes submitted IDs
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            // Get submitted related show IDs
            $submittedIds = [];
            if (isset($data['relatedShows']) && is_array($data['relatedShows'])) {
                $submittedIds = array_filter(array_map('intval', $data['relatedShows']));
            }

            // Re-add relatedShows field with query that includes submitted IDs
            $form->add('relatedShows', EntityType::class, [
                'class' => Show::class,
                'choice_label' => function(Show $show) {
                    return $show->getTitlesForSelect();
                },
                'query_builder' => function (EntityRepository $er) use ($submittedIds) {
                    $qb = $er->createQueryBuilder('sh')
                        ->orderBy('sh.japaneseTitle', 'ASC');

                    if (!empty($submittedIds)) {
                        // Include all submitted show IDs in the valid choices
                        $qb->where('sh.id IN (:submittedIds)')
                            ->setParameter('submittedIds', $submittedIds);
                    } else {
                        // Return empty result set if nothing submitted
                        $qb->where('1 = 0');
                    }

                    return $qb;
                },
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Show::class,
        ]);
    }
}
