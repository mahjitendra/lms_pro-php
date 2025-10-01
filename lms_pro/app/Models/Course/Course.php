<?php

namespace LmsPro\App\Models\Course;

use LmsPro\Core\Model;
use LmsPro\App\Models\User;

class Course extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected static $table = 'courses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'instructor_id',
    ];

    /**
     * Get the category that the course belongs to.
     *
     * @return Category|null
     */
    public function category()
    {
        return Category::find($this->category_id);
    }

    /**
     * Get the instructor (user) who created the course.
     *
     * @return User|null
     */
    public function instructor()
    {
        return User::find($this->instructor_id);
    }
}