<?php

namespace App\PL\Form\Match;

use App\BL\Match\MatchModel;
use App\BL\Util\DateTimeUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchSetResultFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var MatchModel */
        $match = $options['match'];
        if ($options['use_points']){
            $builder
                ->add('points_first', NumberType::class, [
                    'label' => 'Points for ' . $match->getParticipant1()?->getParticipantName() ?? 'Participant was not entered',
                    'getter' => fn (MatchModel $match, FormInterface $form): ?float => $match->getParticipant1()?->getPoints(),
                    'setter' => function (MatchModel $match, float $points_first, FormInterface $form): void {
                        $match->getParticipant1()?->setPoints($points_first);
                    }
                ])
                ->add('points_second', NumberType::class, [
                    'label' => 'Points for ' . $match->getParticipant2()?->getParticipantName() ?? 'Participant was not entered',
                    'getter' => fn (MatchModel $match, FormInterface $form): ?float => $match->getParticipant2()?->getPoints(),
                    'setter' => function (MatchModel $match, float $points_second, FormInterface $form): void {
                        $match->getParticipant2()?->setPoints($points_second);
                    }
                ]);
        }
        else{
            $builder
                ->add('duration_first', DateIntervalType::class, [
                    'label' => 'Points for ' . $match->getParticipant1()?->getParticipantName() ?? 'Participant was not entered',
                    'getter' => fn (MatchModel $match, FormInterface $form): ?\DateInterval => $match->getParticipant1()?->getCompletionTime(),
                    'setter' => function (MatchModel $match, \DateInterval $duration_first, FormInterface $form): void {
                        $match->getParticipant1()?->setCompletionTime($duration_first);
                    },
                    'widget' => 'integer',
                    'attr' => ['class' => 'date-interval row'],
                    'data' => new \DateInterval('PT0S'),
                    'with_minutes'  => true,
                    'with_seconds'  => true,
                    'with_hours' => true,
                    'with_days' => false,
                    'with_months' => false,
                    'with_years' => false,
                    'input' => 'dateinterval'
                ])
                ->add('duration_second', DateIntervalType::class, [
                    'label' => 'Points for ' . $match->getParticipant2()?->getParticipantName() ?? 'Participant was not entered',
                    'getter' => fn (MatchModel $match, FormInterface $form): ?\DateInterval => $match->getParticipant2()?->getCompletionTime(),
                    'setter' => function (MatchModel $match, \DateInterval $duration_second, FormInterface $form): void {
                        $match->getParticipant2()?->setCompletionTime($duration_second);
                    },
                    'widget' => 'integer',
                    'attr' => ['class' => 'date-interval row'],
                    'data' => new \DateInterval('PT0S'),
                    'with_minutes'  => true,
                    'with_seconds'  => true,
                    'with_hours' => true,
                    'with_days' => false,
                    'with_months' => false,
                    'with_years' => false,
                    'input' => 'dateinterval'
                ]);
        }

        $builder
            ->add('submit', SubmitType::class, ['label' => 'Add']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatchModel::class,
            'match' => null,
            'use_points' => false
        ])
        ->setAllowedTypes('match', MatchModel::class)
        ->setAllowedTypes('use_points', 'bool');
    }
}
