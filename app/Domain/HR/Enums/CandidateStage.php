<?php

declare(strict_types=1);

namespace App\Domain\HR\Enums;

enum CandidateStage: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';
}
