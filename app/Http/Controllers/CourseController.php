<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Schedule;
use App\Models\Archive;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Validator;

class CourseController extends Controller
{
    /**
     * Creates a Course based on inputs from Request
     * POST: /api/courses
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            "name" => "required|string|min:4|max:32",
            "code" => "required|min:2",
            "credit_unit" => "required|integer|min:1",
            "description" => "required|string|min:4",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Request didn't pass the validation",
                "errors" => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        $course = Course::create([
            "name" => $validated["name"],
            "code" => $validated["code"],
            "credit_unit" => $validated["credit_unit"],
            "description" => $validated["description"]
        ]);
        return response()->json([
            "ok" => true,
            "data" => $course,
            "message" => "Course has been created and assigned to the user."
        ], 201);
    }

    /**
     * Retrieve all courses
     * GET: /api/courses
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(){
        return response()->json([
            "ok" => true,
            "data" => Course::all(),
            "message" => "Courses information has been retrieved."
        ], 200);
    }

    /**
     * Retrieve specific course using id
     * GET: /api/courses/{course}
     * @param Course $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Course $course){
        return response()->json([
            'ok' => true,
            'message' => 'Course specific information has been retrieved.',
            'data' => $course
        ], 200);
    }

    /**
     * Update specific course using inputs from Request and id from URI
     * PATCH: /api/courses/{course}
     * @param Request $request
     * @param Course $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Course $course){
        $validator = Validator::make($request->all(), [
            "name" => "sometimes|string|min:4|max:32",
            "code" => "sometimes|min:2",
            "credit_unit" => "sometimes|integer|min:1",
            "description" => "sometimes|string|min:4",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "ok" => false,
                "message" => "Request didn't pass the validation",
                "errors" => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        // Update the existing course instead of creating a new one
        $course->update($validated);

        return response()->json([
            "ok" => true,
            "data" => $course,
            "message" => "Course has been updated!"
        ], 200);
    }

   /**
     * Soft delete a Course
     * @param $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function softDeleteCourse($courseId)
    {
        $user = Course::findOrFail($courseId);
        $user->delete();

        return response()->json(['ok' => true, 'id' => $courseId , 'message' => 'Course soft deleted successfully']);
    }

    /**
     * Restore a soft deleted user
     * @param $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restoreCourse($courseId)
    {
        $course = Course::withTrashed()->findOrFail($courseId);
        $course->restore();

        return response()->json(['ok' => true, 'id' => $courseId , 'message' => 'Course restored successfully']);
    }

 /**
     * Force delete a user
     * @param $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($courseId)
    {
        $course = Course::withTrashed()->findOrFail($courseId);
        $course->forceDelete();

        return response()->json(['message' => 'Course permanently deleted']);
    }

      /**
     * Retrive all Course soft deleted from Request
     * GET: /api/course/{course}
     * @param Request
     * @param Course
     * @return \Illuminate\Http\JsonResponse
     */
    public function ArchivedCourse(Request $request, Course $course)
    {
        $course = Course::onlyTrashed()->get();
        return response()->json([
            'message' => 'Soft-deleted courses retrieved successfully',
            'data' => $course
        ], 200);
    }
}

