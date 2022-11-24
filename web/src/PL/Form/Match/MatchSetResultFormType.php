<?php

namespace App\PL\Form\Match;

use App\BL\Match\MatchModel;
use App\BL\Util\DateTimeUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
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
                ->add('duration_first', TimeType::class, [
                    'label' => 'Points for ' . $match->getParticipant1()?->getParticipantName() ?? 'Participant was not entered',
                    'getter' => fn (MatchModel $match, FormInterface $form): ?int
                        => ($match->getParticipant1()?->getCompletionTime() !== null) ? DateTimeUtil::dateIntervalToSeconds($match->getParticipant1()->getCompletionTime()) : null,
                    'setter' => function (MatchModel $match, int $duration_first, FormInterface $form): void {
                        $match->getParticipant1()?->setCompletionTime(new \DateInterval("PT{$duration_first}S"));
                    },
                    'widget' => 'single_text',
                    'input' => 'timestamp',
                    'html5' => true,
                    'with_minutes' => true,
                    'with_seconds' => true
                ])
                ->add('duration_second', TimeType::class, [
                    'label' => 'Points for ' . $match->getParticipant2()?->getParticipantName() ?? 'Participant was not entered',
                    'getter' => fn (MatchModel $match, FormInterface $form): ?int
                        => ($match->getParticipant2()?->getCompletionTime() !== null) ? DateTimeUtil::dateIntervalToSeconds($match->getParticipant2()->getCompletionTime()) : null,
                    'setter' => function (MatchModel $match, int $duration_second, FormInterface $form): void {
                        $match->getParticipant2()?->setCompletionTime(new \DateInterval("PT{$duration_second}S"));
                    },
                    'widget' => 'single_text',
                    'input' => 'timestamp',
                    'html5' => true,
                    'with_minutes' => true,
                    'with_seconds' => true
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
