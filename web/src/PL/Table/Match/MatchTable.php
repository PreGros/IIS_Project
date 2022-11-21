<?php

namespace App\PL\Table\Match;

use App\PL\Table\Base\BaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchTable extends BaseTable
{
    public function init(array $options = []): self
    {
        parent::init($options);

        $this
            ->addColumn('startTime', 'Start time')
            ->addColumn('duration', 'Duration');
        
        if ($this->options['canModify']){
            $this->addColumn('action', 'Action', true);
        }

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('canModify', false)
            ->setDefault('matches', [])
            ->setAllowedTypes('matches', 'array')
            ->setAllowedTypes('canModify', 'bool');
    }

    protected function setData(): \Traversable
    {
        /** @var \App\BL\Match\MatchModel $match */
        foreach ($this->options['matches'] as $match){
            $data = [
                'startTime' => $match->getStartTime()->format('j. n. Y G:i'),
                'duration' => $match->getDuration()->format('%H:%I:%S')
            ];

            if ($this->options['canModify']){
                // TODO: modify
            }

            yield $data;
        }
    }

}
