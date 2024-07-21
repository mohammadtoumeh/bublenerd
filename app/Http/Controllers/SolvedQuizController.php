<?php

namespace App\Http\Controllers;

use App\Http\Middleware\MyMiddlewares\IsAdminOrTeacher;
use App\Models\SolvedQuiz;
use App\Http\Requests\StoreSolvedQuizRequest;
use App\Http\Requests\UpdateSolvedQuizRequest;
use App\Services\SolvedQuizService;

class SolvedQuizController extends Controller
{
    protected SolvedQuizService $solvedQuizServices;
    public function __construct(SolvedQuizService $solvedQuizService)
    {
        $this->solvedQuizServices=$solvedQuizService;
        $this->middleware(['auth:api'])->only('create','delete','update');
        $this->middleware(['auth:api'])->only('getById');
    }



    public function create(StoreSolvedQuizRequest $data)
    {
        return $this->solvedQuizServices->create($data->safe()->all());
    }


    public function store()
    {

    }


    public function show(SolvedQuiz $solvedQuiz)
    {
        //
    }


    public function edit(SolvedQuiz $solvedQuiz)
    {
        //
    }


    public function update(UpdateSolvedQuizRequest $request, SolvedQuiz $solvedQuiz)
    {
        //
    }


    public function destroy(SolvedQuiz $solvedQuiz)
    {

    }
}
