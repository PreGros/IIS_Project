<?php

namespace App\PL\Form\Match;

use App\BL\Match\MatchManager;
use App\BL\Match\MatchModel;
use App\BL\Tournament\TournamentParticipantModel;
use App\BL\Util\DateTimeUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchEditFormType extends AbstractType
{
    private MatchManager $matchManager;

    public function __construct(MatchManager $matchManager)
    {
        $this->matchManager = $matchManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \App\BL\Match\MatchModel */
        $match = $options['match'];
        $bothDisabled = $match->getPreviousMatchesCount() === 2;
        $disableEnable = $match->getPreviousMatchesCount() > 0;
        $firstDisabled = $disableEnable && $match->getParticipant1() === null;
        $builder
            ->add('first_participant', ChoiceType::class, [
                'choices' => $this->matchManager->getFormatedTournamentParticipants($options['tournament_id'], $match->getParticipant1()?->getTournamentPartId(), $options['one_multiple']),
                'data' => $match->getParticipant1()?->getTournamentPartId(),
                'mapped' => false,
                'required' => false,
                'disabled' => $disableEnable && ($bothDisabled || $firstDisabled),
                'placeholder' => $disableEnable && ($bothDisabled || $firstDisabled) ? 'From previous match' : 'Participant not chosen'
            ])
            ->add('second_participant', ChoiceType::class, [
                'choices' => $this->matchManager->getFormatedTournamentParticipants($options['tournament_id'], $match->getParticipant2()?->getTournamentPartId(), $options['one_multiple']),
                'data' => $match->getParticipant2()?->getTournamentPartId(),
                'mapped' => false,
                'required' => false,
                'disabled' => $disableEnable && ($bothDisabled || !$firstDisabled),
                'placeholder' => $disableEnable && ($bothDisabled || !$firstDisabled) ? 'From previous match' : 'Participant not chosen'
            ])
            ->add('start_time', DateTimeType::class, [
                'label' => 'Start time - Warning collisions with other matches are not checked',
                'getter' => fn (MatchModel $match, FormInterface $form): \DateTimeInterface => $match->getStartTime(),
                'setter' => function (MatchModel $match, \DateTimeInterface $start_time, FormInterface $form): void {
                    $match->setStartTime($start_time);
                }
            ])
            ->add('duration', DateIntervalType::class, [
                'label' => 'Duration - Warning collisions with other matches are not checked',
                'getter' => fn (MatchModel $match, FormInterface $form): \DateInterval => $match->getDuration(),
                'setter' => function (MatchModel $match, \DateInterval $duration, FormInterface $form): void {
                    $match->setDuration($duration);
                },
                'widget' => 'integer',
                'attr' => ['class' => 'date-interval row'],
                'with_minutes'  => true,
                'with_seconds'  => true,
                'with_hours' => true,
                'with_days' => false,
                'with_months' => false,
                'with_years' => false,
                'input' => 'dateinterval'
            ]);

        $builder
            ->add('submit', SubmitType::class, ['label' => 'Add']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatchModel::class,
            'match' => null,
            'tournament_id' => 0,
            'one_multiple' => false
        ]);
    }
}
