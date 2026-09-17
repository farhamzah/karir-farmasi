<?php

namespace App\Contracts;

use App\Data\CoreDirectoryPerson;
use App\Data\CoreStudyProgram;

interface CoreDirectoryGateway
{
    public function findPerson(string $coreUserId): CoreDirectoryPerson;

    public function findStudyProgram(string $programId): CoreStudyProgram;

    /** @return list<CoreStudyProgram> */
    public function listScopedPrograms(): array;
}
