<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::all();

        if($students->count() > 0)
        {
            return response()->json([
                'status' => 200,
                'studens' => $students
            ], 200);
        }else{
            return response()->json([
                'status' => 404,
                'studens' => 'No Records Found'
            ], 404);
        }

    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:191',
            'email'  => 'required|unique:students|email|max:191',
            'phone'  => 'required|digits:11',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }else{
            $student = Student::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            
            if($student)
            {
                return response()->json([
                    'status' => 200,
                    'message' => "student created successfully"
                ] ,200);

            }else{
                return response()->json([
                    'status' => 500,
                    'message' => "something went wrong"
                ] ,500);
            }
        }
    }

    public function show($id)
    {
        $student = Student::find($id);

        if($student)
        {
            return response()->json([
                'status' => 200,
                'student' => $student
            ] ,200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No student found"
            ] ,404);
        }
    }

    public function edit($id)
    {
        $student = Student::find($id);

        if($student)
        {
            return response()->json([
                'status' => 200,
                'student' => $student
            ] ,200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No student found"
            ] ,404);
        }
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:191',
            'email'  => 'required|unique:students|email|max:191',
            'phone'  => 'required|digits:11',
        ]);

        if($validator->fails())
        {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }else{
            $student = Student::find($id);

            if($student)
            {
                $student->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => "student updated successfully"
                ] ,200);
            }else{
                return response()->json([
                    'status' => 404,
                    'message' => "No such student found!"
                ] ,404);
            }
        }
    }

    public function destroy($id)
    {
        $student = Student::find($id);
        if($student)
        {
            $student->delete();
            return response()->json([
                'status' => 200,
                'message' => "student deleted successfully"
            ] ,200);
        }else{
            return response()->json([
                'status' => 404,
                'message' => "No such student found!"
            ] ,404);
        }
    }

    public function testGet()
    {
        $response = Http::get('http://www.testapi.com/api/students');

        $jsonData = $response->json();

        dd($jsonData);
    }

    public function testPost()
    {
        $response = http::post('http://www.testapi.com/api/students', [
            "name" => "maged ahmed",
            "email" => "mahmed@gmail.com",
            "phone" => "01024827469"
        ]);

        $jsonData = $response->json();

        dd($jsonData);
    }

    public function testPut()
    {
        $response = http::put('http://www.testapi.com/api/students/3/edit', [
            "name" => "maged ahmed",
            "email" => "mahmed@gmail.com",
            "phone" => "01024827469"
        ]);

        $jsonData = $response->json();

        dd($jsonData);
    }
}
