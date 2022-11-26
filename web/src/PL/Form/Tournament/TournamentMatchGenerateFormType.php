<?php

namespace App\PL\Form\Tournament;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentMatchGenerateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('duration', DateIntervalType::class, [
                'label' => 'Match duration',
                'widget' => 'integer',
                'attr' => ['class' => 'date-interval row'],
                'data' => new \DateInterval('PT30M'),
                'with_minutes'  => true,
                'with_seconds'  => true,
                'with_hours' => true,
                'with_days' => false,
                'with_months' => false,
                'with_years' => false,
                'input' => 'dateinterval'
            ])
            ->add('break', DateIntervalType::class, [
                'label' => 'Break duration',
                'widget' => 'integer',
                'attr' => ['class' => 'date-interval row'],
                'data' => new \DateInterval('PT5M'),
                'with_minutes'  => true,
                'with_seconds'  => true,
                'with_hours' => true,
                'with_days' => false,
                'with_months' => false,
                'with_years' => false,
                'input' => 'dateinterval'
            ])
            ->add('setParticipants', CheckboxType::class, [
                'label' => 'Automatically add participants to matches',
                'data' => true,
                'required' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Generate Matches',
                'disabled' => $options['disabled'],
                'attr' => [
                    'title' => $options['titleDisabled'],
                    'class' => 'w-label btn-primary'
                    ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'duration' => \DateInterval::class,
            'titleDisabled' => ""
        ]);
    }
}
