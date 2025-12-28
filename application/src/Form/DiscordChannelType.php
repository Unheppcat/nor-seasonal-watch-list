<?php

namespace App\Form;

use App\Entity\DiscordChannel;
use App\Entity\Season;
use App\Entity\Show;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscordChannelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
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
            ->add('hidden')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DiscordChannel::class,
        ]);
    }
}
