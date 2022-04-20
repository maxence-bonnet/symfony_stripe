<?php

namespace App\Form;

use App\Entity\SchedulePhase;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchedulePhaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('iterations')
            ->add('priorityOrder')
            ->add('price', PriceType::class, [
                // 'class' => PriceType::class,
                // 'query_builder' => function (EntityRepository $er) {
                //     return $er->createQueryBuilder('price')
                //         ->select('price', 'prod', 's')
                //         ->innerJoin('price.product', 'prod')
                //         ->innerJoin('prod.subscription', 's')
                //         ->orderBy('u.username', 'ASC');
                // },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SchedulePhase::class,
        ]);
    }
}
