<?php

namespace App\PL\Form\Tournament;

use App\BL\Tournament\MatchingType;
use App\BL\Tournament\ParticipantType;
use App\BL\Tournament\TournamentModel;
use App\BL\Tournament\WinCondition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentMatchGenerationFormType extends AbstractType
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
            ->add('submit', SubmitType::class, [
                'label' => 'Generate Matches'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'duration' => \DateInterval::class
        ]);
    }
}
