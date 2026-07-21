<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle status of a Rentencheck.
 *
 * Draft     — still being filled in; the advisor can edit steps.
 * Completed — finalized; the analysis snapshot is frozen and the PDF generated.
 */
enum RentencheckStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
}
