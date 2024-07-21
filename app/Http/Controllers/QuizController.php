<?php

namespace App\Http\Controllers;

use App\Http\Middleware\MyMiddlewares\IsAdminOrTeacher;
use App\Models\Quiz;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Services\QuizService;

class QuizController extends Controller
{
    protected QuizService $quizService;
   public function __construct(QuizService $quizService)
   {
       $this->quizService=$quizService;
       $this->middleware(['auth:api', IsAdminOrTeacher::class])->only('create','delete','update');
       $this->middleware(['auth:api'])->only('getById');
   }

    public function index()
    {
        return $this->quizService->index();
    }
    public function getById(int $courseId)
    {
        return $this->quizService->getById($courseId);
    }

    public function getByUser(int $id)
    {
        return $this->quizService->getByUser($id);
    }

    public function create(StoreQuizRequest $data)
    {
         return $this->quizService->create($data->safe()->all());
    }


    public function update(UpdateQuizRequest $data, int $id)
    {
        return $this->quizService->update($data->safe()->all(),$id);
    }


    public function delete(int $id)
    {
        return $this->quizService->delete($id);
    }


}
