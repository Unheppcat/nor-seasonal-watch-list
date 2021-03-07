<?php

namespace App\Form;

use App\Entity\Election;
use App\Entity\Season;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Election::class,
        ]);
    }
}
