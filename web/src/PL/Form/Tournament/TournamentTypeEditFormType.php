<?php

namespace App\PL\Form\Tournament;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TournamentTypeEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Tournament name'
            ])
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'w-50 btn-primary']
            ])
        ;
    }
}
