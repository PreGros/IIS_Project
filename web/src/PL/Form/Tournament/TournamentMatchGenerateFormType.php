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
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentMatchGenerationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('duration', TimeType::class, [
                'label' => 'Match duration',
                'widget' => 'single_text',
                'html5' => true,
                'with_minutes'  => true,
                'with_seconds'  => true
            ])
            ->add('brek', TimeType::class, [
                'label' => 'Break duration',
                'widget' => 'single_text',
                'html5' => true,
                'with_minutes'  => true,
                'with_seconds'  => true
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
