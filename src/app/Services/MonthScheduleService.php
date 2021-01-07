<?php

namespace App\Services;

use App\Repositories\ScheduleReservationRepository;
use App\Models\ScheduleStatus;
use App\Models\Category;
use App\Models\Setting;
use App\NotificationServices\SendService;

class MonthScheduleService
{
    protected $subMonthName = '{month_name}';
    protected $emptyLine = '';

    protected $scheduleReservationRepository;
    protected $scheduleMasterService;
    protected $settingService;
    protected $formatService;
    protected $sendService;

    public function __construct(
        ScheduleReservationRepository $scheduleReservationRepository,
        ScheduleMasterService $scheduleMasterService,
        SettingService $settingService,
        FormatService $formatService,
        SendService $sendService
    ) {
        $this->scheduleReservationRepository = $scheduleReservationRepository;
        $this->scheduleMasterService = $scheduleMasterService;
        $this->settingService = $settingService;
        $this->formatService = $formatService;
        $this->sendService = $sendService;
    }

    protected function getHeaderLine($headerString)
    {
        return __('mail.header.prefix') . $headerString . __('mail.header.suffix');
    }

    protected function addLines($target, $linesAdd)
    {
        foreach ($linesAdd as $line) {
            $target->add($line);
        }
    }

    protected function getSettingValue($category, $key)
    {
        return $this->settingService->get($category, $key)->value;
    }

    protected function getScheduleList($month)
    {
        return $this->scheduleReservationRepository->selectScheduleList(true, $month->first_day, $month->last_day)
            ->filter(fn ($schedule) => $schedule->schedule_status->bulk_change_mode)
            ->values();
    }

    public function prepareSendInfo($monthId)
    {
        $month = $this->scheduleReservationRepository->selectMonth($monthId);
        return [
            'month' => $month,
            'mail_to' => $this->prepareMailTo(),
            'subject' => $this->prepareSubject($month),
            'message' => $this->prepareMessage($month),
        ];
    }

    public function prepareMailTo()
    {
        return $this->getSettingValue(Category::RESERVATION, Setting::KEY_MAIL_TO);
    }

    public function prepareSubject($month)
    {
        $template = $this->getSettingValue(Category::RESERVATION, Setting::KEY_MAIL_SUBJECT);
        $substitute = $this->subMonthName;
        return str_replace($substitute, $month->name, $template);
    }

    public function prepareMessage($month)
    {
        $lineCollection = collect();

        $lineCollection->add($this->prepareMessageBegin($month));
        $lineCollection->add($this->emptyLine);

        $this->addLines($lineCollection, $this->prepareMessageScheduleListLineCollection($month));

        $this->addLines($lineCollection, $this->prepareMessageNotesLineCollection());
        $lineCollection->add($this->emptyLine);

        $lineCollection->add($this->prepareMessageEnd());
        $lineCollection->add($this->emptyLine);

        return $lineCollection->join("\n");
    }

    public function prepareMessageBegin($month)
    {
        $template = $this->getSettingValue(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_BEGIN);
        $substitute = $this->subMonthName;
        return str_replace($substitute, $month->name, $template);
    }

    public function prepareMessageScheduleListLineCollection($month)
    {
        $scheduleList = $this->getScheduleList($month);
        $usageList = $this->scheduleMasterService->getScheduleUsageList()->filter(fn ($usage) => $usage->is_public)->values();
        $usageLineCollectionMap = [];
        foreach ($usageList as $usage) {
            $usageLineCollectionMap[$usage->id] = collect();
        }
        foreach ($scheduleList as $schedule) {
            if (array_key_exists($schedule->schedule_usage_id, $usageLineCollectionMap)) {
                $usageLineCollectionMap[$schedule->schedule_usage_id]->add($this->prepareMessageSchedule($schedule));
            }
        }

        $result = collect();
        foreach ($usageList as $usage) {
            $result->add($this->getHeaderLine($usage->name));
            $usageLineCollection = $usageLineCollectionMap[$usage->id];
            if ($usageLineCollection->isEmpty()) {
                $result->add(__('mail.none'));
            } else {
                $this->addLines($result, $usageLineCollection);
            }
            $result->add($this->emptyLine);
        }
        return $result;
    }

    public function prepareMessageSchedule($schedule)
    {
        $date = $this->formatService->dateMonthDayWeek($schedule->ymd);
        $beginsAt = $this->formatService->time($schedule->begins_at);
        $endsAt = $this->formatService->time($schedule->ends_at);
        $place = $schedule->schedule_place->abbreviation;
        return "${date} ${beginsAt}-${endsAt} ${place}";
    }

    public function prepareMessageNotesLineCollection()
    {
        $notes = $this->getSettingValue(Category::RESERVATION_PUBLIC, Setting::KEY_NOTES);
        if (!$notes) {
            $notes = __('mail.none');
        }
        return collect([$this->getHeaderLine(__('mail.header.notes')), $notes]);
    }

    public function prepareMessageEnd()
    {
        return $this->getSettingValue(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_END);
    }

    public function send($monthId, $sendInfo)
    {
        $this->sendService->send($sendInfo);

        $month = $this->scheduleReservationRepository->selectMonth($monthId);
        $bulkChangeScheduleList = $this->getScheduleList($month)
            ->filter(fn ($schedule) => $schedule->schedule_status->bulk_change_mode == ScheduleStatus::BULK_CHANGE_FROM)
            ->values();
        if (!$bulkChangeScheduleList->isEmpty()) {
            $fixedStatus = $this->scheduleMasterService->getFixedScheduleStatus();
            foreach ($bulkChangeScheduleList as $schedule) {
                $schedule->schedule_status_id = $fixedStatus->id;
            }
            $this->scheduleReservationRepository->saveEntityList($bulkChangeScheduleList);

            $month->schedule_status_id = $fixedStatus->id;
            $this->scheduleReservationRepository->saveEntity($month);
        }

        return $monthId;
    }
}
