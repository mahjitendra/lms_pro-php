<?php

namespace LmsPro\App\Controllers\Course;

use LmsPro\Core\Controller;
use LmsPro\App\Models\Course\Course;
use LmsPro\App\Models\Course\Category;
use LmsPro\Core\Request;
use LmsPro\App\Services\AuthService;

class CourseController extends Controller
{
    /**
     * @var AuthService
     */
    protected $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthService();

        // Protect all course routes
        if (!$this->auth->check()) {
            $this->redirect('/login');
        }
    }

    /**
     * Display a listing of all courses.
     */
    public function index()
    {
        $courses = Course::all();
        $this->view('courses.index', ['courses' => $courses]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $categories = Category::all();
        $this->view('courses.create', ['categories' => $categories]);
    }

    /**
     * Store a newly created course in the database.
     */
    public function store()
    {
        $course = new Course();
        $course->fill([
            'title' => Request::get('title'),
            'description' => Request::get('description'),
            'category_id' => Request::get('category_id'),
            'instructor_id' => $this->auth->user()->id
        ]);
        $course->save();

        $this->redirect('/courses');
    }

    /**
     * Show the form for editing a specific course.
     *
     * @param int $id
     */
    public function edit($id)
    {
        $course = Course::find($id);
        if (!$course) {
            // In a real app, show a 404 page
            return $this->redirect('/courses');
        }

        $categories = Category::all();
        $this->view('courses.edit', ['course' => $course, 'categories' => $categories]);
    }

    /**
     * Update the specified course in the database.
     *
     * @param int $id
     */
    public function update($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return $this->redirect('/courses');
        }

        $course->fill([
            'title' => Request::get('title'),
            'description' => Request::get('description'),
            'category_id' => Request::get('category_id')
        ]);
        $course->save();

        $this->redirect('/courses');
    }

    /**
     * Remove the specified course from the database.
     *
     * @param int $id
     */
    public function destroy($id)
    {
        $course = Course::find($id);
        if ($course) {
            $course->delete();
        }

        $this->redirect('/courses');
    }
}