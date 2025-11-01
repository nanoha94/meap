<?php

namespace App\Services;

use App\Models\Group;
use App\Services\AbstractDomainService;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseTypeService extends AbstractDomainService
{

    protected function getResourceName(): string
    {
        return __('api.attributes.course_type');
    }

    protected function getGroupRelation(Group $group): HasMany
    {
        return $group->courseTypes();
    }
}
