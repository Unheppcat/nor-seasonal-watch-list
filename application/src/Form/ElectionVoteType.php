<?php

namespace App\Form;

use App\Entity\Election;
use App\Entity\ElectionVote;
use App\Entity\Season;
use App\Entity\Show;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElectionVoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        for ($i = 1; $i <= $options['show_count']; $i++) {
            $choices[(string)$i] = $i;
        }
        if ($options['election_type'] === 'simple') {
            $builder
                ->add('chosen', CheckboxType::class, [
                    'label' => 'Choose this show',
                    'required' => false,
                    'attr' => [ 'autocomplete' => 'off' ],
                ]);
        } else {
            $builder->
                add('rank', ChoiceType::class, [
                    'label' => 'Rank',
                    'required' => false,
                    'placeholder' => 'No opinion',
                    'choices' => $choices,
                    'attr' => [ 'autocomplete' => 'off' ],
                ]);
        }

        if (isset($options['show_vote_only'])) {
            $builder
                ->add('election', HiddenType::class, ['property_path' => 'election.id'])
                ->add('season', HiddenType::class, ['property_path' => 'season.id'])
                ->add('animeShow', HiddenType::class, ['property_path' => 'animeShow.id']);
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
                $form = $event->getForm();
                $form
                    ->remove('election')
                    ->add('election', EntityType::class,
                        ['class' => Election::class, 'multiple' => false, 'expanded' => false, 'choice_label' => 'getElection']
                    )
                    ->remove('season')
                    ->add('season', EntityType::class,
                        ['class' => Season::class, 'multiple' => false, 'expanded' => false, 'choice_label' => 'getSeason',]
                    )
                    ->remove('animeShow')
                    ->add('animeShow', EntityType::class,
                        ['class' => Show::class, 'multiple' => false, 'expanded' => false, 'choice_label' => 'getShow']
                    );
            });
        } else {
            $builder
                ->add('election', EntityType::class, [
                    'class' => Election::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->join('s.season', 'season')
                            ->orderBy('season.name', 'ASC')
                            ->addOrderBy('s.startDate', 'ASC');
                    },
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                ])
                ->add('season', EntityType::class, [
                    'class' => Season::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->orderBy('s.rankOrder', 'ASC');
                    },
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                ])
                ->add('animeShow', EntityType::class, [
                    'class' => Show::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->orderBy('s.japaneseTitle', 'ASC');
                    },
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                ])
                ->add('user', EntityType::class, [
                    'class' => User::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->orderBy('s.username', 'ASC');
                    },
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ElectionVote::class,
            'show_vote_only' => false,
            'election_type' => 'simple',
            'show_count' => 0,
            'form_key' => 0,
        ]);
    }
}
