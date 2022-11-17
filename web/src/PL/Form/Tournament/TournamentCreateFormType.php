<?php

namespace App\PL\Form\Tournament;

use App\BL\Tournament\MatchingType;
use App\BL\Tournament\ParticipantType;
use App\BL\Tournament\TournamentModel;
use App\BL\Tournament\WinCondition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentCreateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Tournament name'
            ])
            ->add('description', TextareaType::class, [
                'required' => false
            ])
            ->add('participantType', ChoiceType::class, [
                'choices' => ParticipantType::getTypes()
            ])
            ->add('maxTeamMemberCount', IntegerType::class, [
                'required' => false
            ])
            ->add('minTeamMemberCount', IntegerType::class, [
                'required' => false
            ])
            ->add('maxParticipantCount', IntegerType::class)
            ->add('minParticipantCount', IntegerType::class)
            ->add('date', DateTimeType::class)
            ->add('prize', TextType::class, [
                'required' => false
            ])
            ->add('venue', TextType::class)
            ->add('registrationDateStart', DateTimeType::class)
            ->add('registrationDateEnd', DateTimeType::class)
            ->add('winCondition', ChoiceType::class, [
                'choices' => WinCondition::getTypes()
            ])
            ->add('matchingType', ChoiceType::class, [
                'choices' => MatchingType::getTypes()
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TournamentModel::class,
        ]);
    }
}
