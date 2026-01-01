<?php

namespace App\Form;

use App\Entity\Election;
use App\Entity\Season;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (supports Markdown)',
                'required' => false,
            ])
            ->add('electionType', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Simple' => Election::SIMPLE_ELECTION,
                    'Ranked Choice' => Election::RANKED_CHOICE_ELECTION,
                ]
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start date and time',
                'date_label' => false,
                'date_widget' => 'single_text',
                'time_label' => false,
                'time_widget' => 'single_text',
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End date and time',
                'date_label' => false,
                'date_widget' => 'single_text',
                'time_label' => false,
                'time_widget' => 'single_text',
            ])
            ->add('season', EntityType::class, [
                'class' => Season::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.rankOrder', 'ASC');
                },
                'expanded' => false,
                'multiple'=> false,
                'required' => true,
            ])
            ->add('maxVotes', NumberType::class, [
                'required' => false,
                'label' => 'Max votes, empty = no limit',
            ])
            ->add('restrictedAccess', CheckboxType::class, [
                'required' => false,
                'label' => 'Restricted access',
                'help' => 'Check this to restrict voting to specific users only',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Election::class,
        ]);
    }
}
