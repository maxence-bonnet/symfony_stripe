<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\TestClock;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        // Cannot simply search with "where('c.customer is NULL')": A single-valued association path expression to an inverse side is not supported in DQL queries
                        ->leftJoin('u.customer', 'c')
                        ->where('c is NULL')
                        ->orderBy('u.id', 'DESC');
                },
                'placeholder' => '',
            ])
            ->add('testClock', EntityType::class, [
                'class' => TestClock::class,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
