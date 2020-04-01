<?php

namespace App\Processors\Admin;

use App\Model\Course;
use App\Model\Order;
use App\Model\User;
use App\ObjectProcessor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Orders extends ObjectProcessor
{

    protected $class = '\App\Model\Order';
    protected $scope = 'orders';
    /** @var Builder $conditions */
    protected $conditions;

    /**
     * @param Order $record
     * @return bool|string
     */
    protected function beforeSave($record)
    {
        /** @var Course $course */
        if (!$course = Course::query()->find($this->getProperty('course_id'))) {
            return 'Указанный курс не найден';
        }

        /** @var User $user */
        if (!$user = User::query()->find($this->getProperty('user_id'))) {
            return 'Указанный пользователь не найден';
        }

        if (!$period = $this->getProperty('period')) {
            return 'Вы должны выбрать период оплаты';
        } elseif (!isset($course->price[$period])) {
            return 'Указан неверный период оплаты';
        }
        $record->period = $period;

        if (!$record->exists) {
            $key = [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ];
            if (Order::query()->where($key)->where('status', 1)->count()) {
                return $this->failure('У этого пользователя уже есть неоплаченный заказ, отредактируйте его');
            } elseif (Order::query()->where($key)->where('status', 2)->where('paid_till', '>', date('Y-m-d H:i:s'))->count()) {
                return $this->failure('Этот курс у пользователя уже оплачен');
            }

            $record->manual = true;
            $record->service = 'internal';
            $record->cost = $course->price[$period];
            $record->status = $this->getProperty('status', 2); // Paid
            $record->paid_at = date('Y-m-d H:i:s');
            $record->paid_till = $this->getProperty('paid_till', Carbon::now()->addMonths($period)->toDateTime());
        }

        return true;
    }


    /**
     * @param Builder $c
     *
     * @return Builder
     */
    protected function beforeCount($c)
    {
        $c->select('orders.*');

        if ($query = trim($this->getProperty('query'))) {
            $c->join('courses', 'courses.id', '=', 'orders.course_id');
            $c->join('users', 'users.id', '=', 'orders.user_id');
            $c->where(function (Builder $c) use ($query) {
                $c->where('courses.title', 'LIKE', "%$query%");
                $c->orWhere('users.fullname', 'LIKE', "%$query%");
                $c->orWhere('users.email', 'LIKE', "%$query%");
            });
        }

        if ($date = $this->getProperty('date')) {
            $c->whereBetween('created_at', [$date[0] . ' 00:00:00', $date[1] . ' 23:59:59']);
        }
        if ($course_id = $this->getProperty('course_id')) {
            $c->where('course_id', $course_id);
        }
        if ($service = $this->getProperty('service')) {
            $c->where('service', $service);
        }
        $this->conditions = $c;

        if ($status = (int)$this->getProperty('status')) {
            $c->where('status', $status);
        }

        return $c;
    }

    /**
     * @param Builder $c
     * @return Builder
     */
    protected function afterCount($c)
    {
        $c->with('user:id,fullname,photo_id', 'user.photo:id,updated_at');
        $c->with('course:id,title,cover_id');

        return $c;
    }

    /**
     * @param array $array
     * @return array
     */
    public function prepareList(array $array)
    {
        if ($c = $this->conditions) {
            $c->where(['status' => 2]);
            if ($this->getProperty('service') !== 'internal') {
                $c->where('manual', false);
            }
            $array['total_cost'] = (int)$c->sum('cost');
        }

        return $array;
    }


    /**
     * @param Order $record
     *
     * @return bool|string
     */
    public function beforeDelete($record)
    {
        if ($record->status !== 1) {
            return 'Оплаченные заказы удалять нелья';
        }

        return true;
    }
}