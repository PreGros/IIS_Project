<?php

namespace App\PL\Table\Match;

use App\PL\Table\Base\BaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class MatchTable extends BaseTable
{
    private UrlGeneratorInterface $router;

    public function __construct(Environment $environment, UrlGeneratorInterface $router)
    {
        parent::__construct($environment);
        $this->router = $router;
    }

    public function init(array $options = []): self
    {
        parent::init($options);

        $this
            ->addColumn('startTime', 'Start time')
            ->addColumn('duration', 'Duration')
            ->addColumn('participants', 'Participants')
            ->addColumn('result', 'Result');
        
        if ($this->options['allModifiable']){
            $this->addColumn('action', 'Action', true);
        }

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('allModifiable', false)
            ->setDefault('tournamentId', 0)
            ->setDefault('matches', [])
            ->setAllowedTypes('allModifiable', 'bool')
            ->setAllowedTypes('tournamentId', 'int')
            ->setAllowedTypes('matches', 'array');
    }

    protected function setData(): \Traversable
    {
        /** @var \App\BL\Match\MatchModel $match */
        foreach ($this->options['matches'] as $match){
            $data = [
                'startTime' => $match->getStartTime()->format('j. n. Y G:i'),
                'duration' => $match->getDuration()->format('%H:%I:%S'),
                'participants' => 
                    ($match->getParticipant1()?->getParticipantName() ?? 'From previous match') .
                    ' vs ' .
                    ($match->getParticipant2()?->getParticipantName() ?? 'From previous match'),
                'result' => !$match->hasEnded() ?
                    'Not finished' :
                    ($match->getParticipant1()?->getResult() ?? 'Participant not entered') .
                    ':' .
                    ($match->getParticipant2()?->getResult() ?? 'Participant not entered')
            ];

            if ($this->options['allModifiable']){
                $data['action'] = $this->renderTwigStringColumn(
                    (!$match->hasEnded() ?
                        '<a href="{{ row.edit }}" class="btn btn-secondary">Edit</a>' . ' ' . '<a class="btn btn-primary disabled w-label" title="Cannot set result, match does not ended">Set result</a>' :
                        '<a class="btn btn-secondary disabled w-label" title="Cannot edit, match has ended">Edit</a>' . ' '. '<a href="{{ row.set_result }}" class="btn btn-primary">Set result</a>'
                    ),
                    [
                        'edit' => $this->router->generate('edit_match', ['tournamentId' => $this->options['tournamentId'], 'matchId' => $match->getId()]),
                        'set_result' => $this->router->generate('set_match_result', ['tournamentId' => $this->options['tournamentId'], 'matchId' => $match->getId()])
                    ]
                );
            }

            yield $data;
        }
    }

}
