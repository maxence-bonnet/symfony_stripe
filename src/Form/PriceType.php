<?php

namespace App\Form;

use App\Entity\Price;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price')
            ->add('currency', ChoiceType::class, [
                'choices' => Price::CURRENCY,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => Price::TYPE,
            ])
            ->add('recurringInterval', ChoiceType::class, [
                'placeholder' => '',
                'choices' => Price::RECURRING_INTERVAL,
            ])
            ->add('recurringCount')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Price::class,
        ]);
    }
}
