<?php
    
    //PHP INCLUDES

	include "connect.php";

	if(isset($_POST['selected_employee']) && isset($_POST['selected_services']))
	{

		?>

        <!-- CALENDAR STYLE -->
        
        <style type="text/css">
            
            .calendar_tab
            {
                background: white;
                margin-top: 5px;
                width: 100%;
                position: relative;
                box-shadow: rgba(60, 66, 87, 0.04) 0px 0px 5px 0px, rgba(0, 0, 0, 0.04) 0px 0px 10px 0px;
                overflow: hidden;
                border-radius: 4px;
            }

            .appointment_day
            {
                width: 15%;
                text-align: center;
                display: flex;
                color: rgb(151, 151, 151);
                font-weight: 700;
                -webkit-box-align: center;
                align-items: center;
                -webkit-box-pack: center;
                justify-content: center;
                font-size: 14px;
                line-height: 1.5;
            }

            .appointments_days
            {
                border-top-left-radius: 4px;
                border-top-right-radius: 4px;
                display: flex;
                height: 60px;
                position: relative;
                -webkit-box-pack: justify;
                justify-content: space-between;
                padding: 10px;
                border-bottom: 1px solid rgb(229, 229, 229);
            }

            .available_booking_hours
            {
                display: flex;
                -webkit-box-pack: justify;
                justify-content: space-between;
                padding: 10px;
                border-radius: 4px;
                gap: 10px;
                min-height: 120px;
            }

            .available_booking_hour:hover
            {
                background: #eaf7ef;
                border-color: #99c9a6;
                font-weight: 700;
            }

            .available_booking_hour
            {
                display: block;
                font-size: 14px;
                line-height: 1.3;
                cursor: pointer;
                padding: 12px 8px;
                margin: 6px 0;
                text-align: center;
                border: 1px solid #d9d9d9;
                border-radius: 8px;
                background: #f8f8f8;
                color: #2d2d2d;
                transition: all 0.2s ease;
            }

            input[type="radio"] 
            {
                display: none;
            }

            input[type="radio"]:checked + label 
            {
                background: #2d6b4a;
                color: #fff;
                border-color: #2d6b4a;
                font-weight: 700;
            }

            .available_booking_hours_colum
            {
                width: 15%;
                min-width: 110px;
                text-align: center;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

        </style>

        <!-- END CALENDAR STYLE -->

        <!-- START CALENDAR SLOT -->

        <div class="calendar_slots" style="min-width: 600px;">

            <!-- NEXT 10 DAYS -->

            <div class="appointments_days">
                <?php
                    
                    $indonesian_day_names = array(
                        1 => 'Senin',
                        2 => 'Selasa',
                        3 => 'Rabu',
                        4 => 'Kamis',
                        5 => 'Jumat',
                        6 => 'Sabtu',
                        7 => 'Minggu'
                    );

                    $week_start = new DateTime('monday this week');

                    for($i = 0; $i < 7; $i++)
                    {
                        $appointment_day = clone $week_start;
                        $appointment_day->modify('+' . $i . ' day');
                        $day_id = (int)$appointment_day->format('N');
                        echo "<div class = 'appointment_day'>";
                            echo $indonesian_day_names[$day_id];
                            echo "<br>";
                            echo $appointment_day->format('d M');
                        echo "</div>";
                    } 
                ?>
            </div>

            <!-- DAY HOURS -->

            <div class = 'available_booking_hours'>
                <?php

                    //SELECTED SERVICES
		            $desired_services = $_POST['selected_services'];
		            
                    //SELECTED EMPLOYEE
		            $selected_employee = $_POST['selected_employee'];

            		//Services Duration - End time expected
		            $sum_duration = 0;
		            
                    foreach($desired_services as $service)
		            {
		                
		                $stmtServices = $con->prepare("select service_duration from services where service_id = ?");
		                $stmtServices->execute(array($service));
		                $rowS =  $stmtServices->fetch();
		                $sum_duration += $rowS['service_duration'];
		                
		            }
            
            
$week_start = new DateTime('monday this week');

                    for($i = 0; $i < 7; $i++)
                    {
                        echo "<div class='available_booking_hours_colum'>";

                            $appointment_day = clone $week_start;
                            $appointment_day->modify('+' . $i . ' day');
                            $appointment_date = $appointment_day->format('Y-m-d');
                            $day_id = (int)$appointment_day->format('N');

                            $open_hour = ($day_id == 5) ? 13 : 9;
                            $close_hour = 20;
                            $slot_hour = $open_hour;

                            while($slot_hour < $close_hour)
                            {
                                $start = sprintf('%02d:00', $slot_hour);
                                $start_timestamp = strtotime($appointment_date . ' ' . $start);
                                $end_timestamp = strtotime('+' . $sum_duration . ' minutes', $start_timestamp);
                                $close_timestamp = strtotime($appointment_date . ' ' . sprintf('%02d:00', $close_hour));

                                if($end_timestamp <= $close_timestamp)
                                {
                                    $end_time = date('H:i:s', $end_timestamp);

                                    $stmt_emp = $con->prepare("
                                        Select employee_id
                                        from employees_schedule
                                        where employee_id = ?
                                        and day_id = ?
                                        and ? between from_hour and to_hour
                                        and ? between from_hour and to_hour
                                    ");
                                    $stmt_emp->execute(array($selected_employee,$day_id,$start, $end_time));

                                    if($stmt_emp->rowCount() != 0)
                                    {
                                        $stmt = $con->prepare("
                                            Select * 
                                            from appointments a
                                            where
                                                date(start_time) = ?
                                                and
                                                a.employee_id = ?
                                                and
                                                canceled = 0
                                                and
                                                (
                                                    time(start_time) between ? and ?
                                                    or
                                                    time(end_time_expected) between ? and ?
                                                )
                                        ");
                                        
                                        $stmt->execute(array($appointment_date,$selected_employee,$start,$end_time,$start,$end_time));

                                        if($stmt->rowCount() == 0)
                                        {
                                            ?>
                                                <input type="radio" id="<?php echo $appointment_date." ".$start; ?>" name="desired_date_time" value="<?php echo $appointment_date." ".$start." ".$end_time; ?>">
                                                <label class="available_booking_hour" for="<?php echo $appointment_date." ".$start; ?>"><?php echo $start; ?></label>
                                            <?php
                                        }
                                    }
                                }

                                $slot_hour += 1;
                            }

                        echo "</div>";
                    }
                ?>
            </div>
        </div>
	<?php
	}
    else
    {
        header('location: index.php');
        exit();
    }
?>