<?php

namespace Sinevia\Tasks\Http\Controllers;

/**
 * Contains simple Task management functionality
 */
class TasksController extends \Illuminate\Routing\Controller {

    function anyIndex() {
        return $this->getTaskManager();
    }

    function getTaskManager() {
        $view = request('view');
        $session_order_by = \Session::get('tasks_task_manager_by', 'CreatedAt');
        $session_order_sort = \Session::get('tasks_task_manager_sort', 'DESC');
        $orderby = request('by', $session_order_by);
        $sort = request('sort', $session_order_sort);
        $page = request('page', 0);
        $results_per_page = 20;
        \Session::put('tasks_task_manager_by', $orderby); // Keep for session
        \Session::put('tasks_task_manager_sort', $sort);  // Keep for session

        $filterStatus = request('filter_status', '');
        $filterSearch = request('filter_search', '');
        if ($view == 'trash') {
            $filterStatus = 'Deleted';
        }
        if ($filterStatus == 'Deleted') {
            $view = 'trash';
        }

        $query = \Sinevia\Tasks\Models\Queue::getModel();
        $query = $query->orderBy($orderby, $sort);
        $queuedTasks = $query->paginate(20);

        return view('tasks::admin/task-manager', get_defined_vars());
    }

    function anyTaskDetails() {
        $queuedTaskId = request('QueuedTaskId');
        $queuedTask = \Sinevia\Tasks\Models\Queue::find($queuedTaskId);

        if (is_null($queuedTask)) {
            return json_encode(['status' => 'error', 'message' => 'Task not found']);
        }

        return json_encode(['status' => 'success', 'message' => 'Task found', 'data' => ['Details' => $queuedTask->Details]]);
    }

    function anyTaskRequeue() {
        $queuedTaskId = request('QueuedTaskId');
        $queuedTask = \Sinevia\Tasks\Models\Queue::find($queuedTaskId);

        if (is_null($queuedTask)) {
            return json_encode(['status' => 'error', 'message' => 'Queued task not found']);
        }

        if ($queuedTask->task == null) {
            return json_encode(['status' => 'error', 'message' => 'Task not found']);
        }

        $isSuccess = \Sinevia\Tasks\Models\Queue::queue($queuedTask->task->Id, $queuedTask->getParameters(), $queuedTask->LinkedIds);

        if ($isSuccess) {
            return json_encode(['status' => 'success', 'message' => 'Task requeued']);
        }

        return json_encode(['status' => 'error', 'message' => 'Task faied to be requeued']);
    }

}
