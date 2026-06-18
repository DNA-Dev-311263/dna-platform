<div class="pui-page">
    <h1 style="font-size:22px;font-weight:700;color:#1a2b4a;margin:0 0 16px;"><?php echo Lang::t('_DASHBOARD', 'dashboard'); ?></h1>
</div>
    <div class="yui-u">
        <div class="inline_block_big">
            <h2 class="heading"><?php echo Lang::t('_USERS', 'dashboard'); ?></h2>
            <div class="content">
                <div class="yui-g">
                    <div class="yui-u first">
                        <ul class="link_list">
                            <li><?php echo Lang::t('_TOTAL_USER', 'dashboard') . ': <b id="total_users_count">' . ($user_stats['all'] - 1) . '</b>;'; ?></li>
                            <li><?php echo Lang::t('_SUSPENDED', 'dashboard') . ': <b>' . $user_stats['suspended'] . '</b>;'; ?></li>
                            <?php echo $can_approve ? '<li>' . Lang::t('_WAITING_USERS', 'dashboard') . ': <b>' . $user_stats['waiting'] . '</b>;</li>' : ''; ?>
                            <li><?php echo Lang::t('_REG_LASTSEVENDAYS', 'dashboard') . ':<b>' . $user_stats['register_7d'] . '</b>;'; ?></li>
                            <?php if (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN) { ?>
                                <li><?php echo Lang::t('_INACTIVE_USER', 'dashboard') . ': <b>' . $user_stats['inactive_30d'] . '</b>;'; ?></li>
                                <li><?php echo Lang::t('_ONLINE_USER', 'dashboard') . ': <b>' . $user_stats['now_online'] . '</b>;'; ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <div class="yui-u">
                        <ul class="link_list">
                            <?php if (Docebo::user()->getUserLevelId() == ADMIN_GROUP_GODADMIN) { ?>
                                <li><?php echo Lang::t('_SUPERADMIN_USER', 'dashboard') . ': <b>' . $user_stats['superadmin'] . '</b>;'; ?></li>
                                <li><?php echo Lang::t('_ADMIN_USER', 'dashboard') . ': <b>' . $user_stats['admin'] . '</b>;'; ?></li>
                            <?php } else { ?>
                                <li><?php echo Lang::t('_INACTIVE_USER', 'dashboard') . ': <b>' . $user_stats['inactive_30d'] . '</b>;'; ?></li>
                                <li><?php echo Lang::t('_ONLINE_USER', 'dashboard') . ': <b>' . $user_stats['now_online'] . '</b>;'; ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
                <div class="nofloat"></div>
                <!-- <div style="text-align:center;margin:1em;padding:1em;">
                    <p>Statistics: <span id="users_chart_buttons"></span></p>
                    <div id="users_chart_display"></div>
                </div> --><br/>
                <!--				<div id="users_tabview"></div>-->
                <div class="graph graph--users">
                    <div class="graph__nav">
                        <ul>
                            <li class="js-dashboard-graph graph__label selected" data-tab="accesses"><?php echo Lang::t('_ACCESSES', 'standard'); ?></li>
                            <li class="js-dashboard-graph graph__label" data-tab="registeredusers"><?php echo Lang::t('_TOTAL_USER', 'dashboard'); ?></li>
                        </ul>
                    </div>
                    <div class="graph__container">
                        <div id="user_accesses_chart"
                             class="graph__content graph__content--accesses graph__content--visible">

                        </div>
                        <div id="user_registrations_chart" class="graph__content graph__content--registeredusers">

                        </div>
                    </div>
                    <div id="user_registrations_chart"></div>
                </div>
            </div>
            <div class="inline_block_big">
                <h2 class="heading"><?php echo Lang::t('_COURSES', 'dashboard'); ?></h2>
                <div class="content">
                    <div class="yui-g">
                        <div class="yui-u first">
                            <ul class="link_list">
                                <li><?php echo Lang::t('_TOTAL_COURSE', 'dashboard') . ': <b>' . $course_stats['total'] . '</b>;'; ?></li>
                                <li><?php echo Lang::t('_ACTIVE_COURSE', 'dashboard') . ': <b>' . $course_stats['active'] . '</b>;'; ?></li>
                                <li><?php echo Lang::t('_ACTIVE_SEVEN_COURSE', 'dashboard') . ': <b>' . $course_stats['active_seven'] . '</b>;'; ?></li>
                            </ul>
                        </div>
                        <div class="yui-u">
                            <ul class="link_list">
                                <li>
                                    <?php echo Lang::t('_TOTAL_SUBSCRIPTION', 'dashboard') . ': <b>' . $course_stats['user_subscription'] . '</b>;'; ?>
                                </li>
                                <?php
                                echo checkPerm('moderate', true, 'course', 'lms') ? '<li>' . Lang::t('_WAITING_SUBSCRIPTION', 'dashboard') . ': <b>' . $course_stats['user_waiting'] . '</b>;</li>' : '';
                                $month_1 = (int) date('m');
                                $month_2 = (($month_1 + 12 - 2) % 12) + 1;
                                $month_3 = (($month_1 + 12 - 3) % 12) + 1;
                                ?>
                                <li>
                                    <?php echo Lang::t('_SUBSCRIPTION', 'course') . '&nbsp;' . Lang::t('_MONTH_' . ((int) $month_1 < 10 ? '0' : '') . (int) $month_1) . ': <b>' . $course_months_stats['month_subs_1'] . '</b>;'; ?>
                                </li>
                                <li>
                                    <?php echo Lang::t('_SUBSCRIPTION', 'course') . '&nbsp;' . Lang::t('_MONTH_' . ((int) $month_2 < 10 ? '0' : '') . (int) $month_2) . ': <b>' . $course_months_stats['month_subs_2'] . '</b>;'; ?>
                                </li>
                                <li>
                                    <?php echo Lang::t('_SUBSCRIPTION', 'course') . '&nbsp;' . Lang::t('_MONTH_' . ((int) $month_3 < 10 ? '0' : '') . (int) $month_3) . ': <b>' . $course_months_stats['month_subs_3'] . '</b>;'; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="nofloat"></div>
                    <!-- <div style="text-align:center;margin:1em;padding:1em;">
                        <p>Statistics:&nbsp;<span id="courses_chart_buttons"></span></p>
                        <div id="users_chart_display"></div>
                    </div> --><br/>
                    <!--				<div id="courses_tabview"></div>-->
                    <div class="graph graph--users js-graph-courses">
                        <div class="graph__nav">
                            <ul>
                                <li class="js-dashboard-graph graph__label selected" data-tab="registered"><?php echo Lang::t('_ACCESSES', 'standard'); ?></li>
                                <li class="js-dashboard-graph graph__label" data-tab="ongoing"><?php echo Lang::t('_USER_STATUS_BEGIN', 'standard'); ?></li>
                                <li class="js-dashboard-graph graph__label" data-tab="finished"><?php echo Lang::t('_USER_STATUS_END', 'standard'); ?></li>
                            </ul>
                        </div>
                        <div class="graph__container">
                            <div id="courses_subscriptions_chart"
                                 class="graph__content graph__content--registered graph__content--visible">

                            </div>
                            <div id="courses_startattendings_chart" class="graph__content graph__content--ongoing">

                            </div>
                            <div id="courses_completed_chart" class="graph__content graph__content--finished">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="nofloat"></div>

<script type="text/javascript">
    $(document).ready(function () {

        new Chartist.Bar('#user_accesses_chart', {
            labels: <?php echo $userdata_accesses_js['x_axis']; ?>,
            series: [
                <?php echo $userdata_accesses_js['y_axis']; ?>
            ]
        }, {
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end'
            },
            axisY: {
                // On the y-axis start means left and end means right
                position: 'start'
            }
        });

        new Chartist.Bar('#user_registrations_chart', {
            labels: <?php echo $userdata_registrations_js['x_axis']; ?>,
            series: [
                <?php echo $userdata_registrations_js['y_axis']; ?>
            ]
        }, {
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end'
            },
            axisY: {
                // On the y-axis start means left and end means right
                position: 'start'
            }
        });

        new Chartist.Bar('#courses_subscriptions_chart', {
            labels: <?php echo $coursedata_subscriptions_js['x_axis']; ?>,
            series: [
                <?php echo $coursedata_subscriptions_js['y_axis']; ?>
            ]
        }, {
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end'
            },
            axisY: {
                // On the y-axis start means left and end means right
                position: 'start'
            }
        });

        new Chartist.Bar('#courses_startattendings_chart', {
            labels: <?php echo $coursedata_startattendings_js['x_axis']; ?>,
            series: [
                <?php echo $coursedata_startattendings_js['y_axis']; ?>
            ]
        }, {
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end'
            },
            axisY: {
                // On the y-axis start means left and end means right
                position: 'start'
            }
        });

        new Chartist.Bar('#courses_completed_chart', {
            labels: <?php echo $coursedata_completed_js['x_axis']; ?>,
            series: [
                <?php echo $coursedata_completed_js['y_axis']; ?>
            ]
        }, {
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end'
            },
            axisY: {
                // On the y-axis start means left and end means right
                position: 'start'
            }
        });


    });
</script>
