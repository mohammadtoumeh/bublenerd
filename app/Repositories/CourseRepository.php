<?php

namespace App\Repositories;
use App\Events\CourseCreated;
use App\Exceptions\NotFoundException;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use App\Exceptions\CourseCreatinoException;
use App\Exceptions\CourseUpdateException;
use App\Exceptions\courseNotFoundException;
use App\Models\Course;
use App\Traits\ResponseTrait;
use App\Traits\StorePhotoTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseRepository implements CourseRepositoryInterface
{
    use ResponseTrait;
    use StorePhotoTrait;

    protected Course $course;

    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    public function index()
    {
        return $this->course->get();
    }

    public function getById(int $id)
    {
        $course = $this->course->where('id', $id)  ->where('approved', '1')
            ->first();

        if (!$course) {
            throw new courseNotFoundException();
        }
        return $course;

    }


    public function getByUser(int $userId)
    {
        $user = User::with(['courses' => function ($query) {
            $query->with('userRate');
            $query->where('approved', '1');
            $query->withCount(['ratings as average_rating' => function ($query) {
                $query->select(DB::raw('coalesce(avg(ratings.rating),0)'));
            }]);
        }])->where('id', $userId)->first();

        if (!$user) {

            throw  new NotFoundException('Not found');
        }
        return $user;
    }
    public function getByUSerAndSubject(int $userId,  int $subjectId)

    {
        $course = Course::with('userRate')
          ->withCount(['ratings as average_rating' => function ($query) {
              $query->select(DB::raw('coalesce(avg(ratings.rating),0)'));
                  }])->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('approved'==='1')
            ->get();
        if (!$course) {
            throw new NotFoundException();
        }

        return $course;

    }

    public function getWithUser(int $id)
    {
        $course = $this->course->with('user')
            ->where('id', $id)
            ->where('approved', '1')
            ->first();

        if (!$course) {
            throw new NotFoundException('Not found');
        }

        return $course;
    }

    public function getWithLesson(int $id)
    {

        $course = $this->course->with('lessons')->where('id', $id)->first();

        if (!$course) {

            throw  new NotFoundException('Not found');
        }

        return $course;
    }

    public function create(array $data ,NotificationService $notificationService)
    {
        try {
            DB::beginTransaction();

            $course = new $this->course;
            $course->name = $data['name'];
            $course->price = $data['price'];
            $course->old_price = $data['old_price'];
            $course->description = $data['description'];
            $course->user_id = Auth::id();
            $course->subject_id = $data['subject_id'];

            $course->approved=false;

            $course->photo = isset($data['photo'])
                ? $this->store($data['photo'], 'Course_photos')
                : null;

            $course->save();



            // Tag Extraction
            preg_match_all('/#(\w+)/', $course->description, $matches);
            $tags = collect($matches[1]);

            $tags->each(function ($tagName) use ($course) {
                $tagModel = Tag::firstOrCreate(['name' => $tagName]);
                $course->tags()->attach($tagModel);
            });
            $teacherId = $course->user_id;

            $subscribedUserIds = Subscription::where('teacher_id', $teacherId)->pluck('user_id')->toArray();

            $userTokens = User::whereIn('id', $subscribedUserIds)->pluck('device_token')->toArray();

            if (!empty($userTokens)) {

                $teacherName = $course->user->name;
                $notificationBody = 'A new course: ' .  $course->name . 'by: ' .$teacherName;
                $notificationService->notification($userTokens, 'New Course', $notificationBody);

            }

            DB::commit();
            return $course->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw new CourseCreatinoException("Unable to create course: " . $e->getMessage());
        }
    }
//            $teacherId = $course->user_id;
//
//            $subscribedUserIds = Subscription::where('teacher_id', $teacherId)->pluck('user_id')->toArray();
//
//            $userTokens = User::whereIn('id', $subscribedUserIds)->pluck('device_token')->toArray();
//
//            $notificationSent = false; // Initialize a flag to track notification status
//
//            if (!empty($userTokens)) {
//                $teacherName = $course->user->name;
//                $notificationBody = 'A new course has been released by ' . $teacherName . ': ' . $course->name;
//                $notificationSent = $notificationService->notification($userTokens, 'New Course Created', $notificationBody);
//            }
//
//            DB::commit();
//
//            return ['course' => $course->fresh(), 'notification_sent' => $notificationSent];
//
//        } catch (Exception $e) {
//            DB::rollBack();
//            throw new CourseCreatinoException("Unable to create course: " . $e->getMessage());
//        }
//    }
    public function approved(int $id)
    {
        $course = Course::find($id);

        if ($course && $course->approved === '0') {
            $course->approved = true;
            $course->save();
            return $course;
        }
        throw new CourseCreatinoException('this course cant be approved'  );
    }


    public function update(array $data, int $id)
    {

        try{

            DB::beginTransaction();

            $course = $this->course->find($id);

            if (!$course) {
                throw new courseNotFoundException();
            }

            $course->name = $data['name']?? $course->name;;
            $course->price = $data['price']??$course->price;
            $course->old_price = $data['old_price']??$course->old_price;
            $course->description = $data['description']??$course->description;
            $course->user_id = Auth::id()??$course->user_id;
            if (isset($data['photo'])) {
                $course->photo = $this->store($data['photo'], 'Course_photos');
            }
            $course->save();

            DB::commit();

        }catch(Exception $e){
            DB::rollBack();
            throw new courseUpdateException(("Unable to update course: "). $e->getMessage());

        }
    }

    public function delete(int $id)
    {
        $course = $this->course->find($id);

        if (!$course) {
            throw new CourseNotFoundException();
        }
        $course->delete();

        return $course;
    }

    public function searchForCourse($name)
    {
        $course = Course::with('userRate')
            ->withCount(['ratings as average_rating' => function ($query) {
                $query->select(DB::raw('coalesce(avg(ratings.rating),0)'));
            }])->where('name', 'like', '%' . $name . '%')
            ->get();
        if (!$course) {
            throw new NotFoundException();
        }
            return $course;
    }

}


