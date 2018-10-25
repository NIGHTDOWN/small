define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/activity/index',
                    add_url: 'activity/activity/add',
                    edit_url: 'activity/activity/edit',
                    del_url: 'activity/activity/del',
                    multi_url: 'activity/activity/multi',
                    table: 'activity',
                }
            });

            var table = $("#table");

            // 弹窗大小
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $(".btn-edit-sort").data("area", ["25%", '30%']);
                $(".btn-activity-top").data("area", ["70%", '90%']);
            });


            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showExport: false,
                commonSearch: false,
                columns: [
                    [
                        {field: 'id', title: __('Id'), sortable: true, width: '4%', operate: false},
                        {field: 'order_sort', title: __('Order_sort'), sortable: true, width: '5%', operate: false},
                        {field: 'title', title: __('Title'), operate: false},
                        {field: 'subject_name', title: __('关联标签'), operate: false},
                        {field: 'start_time', title: __('Start_time'), addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'end_time', title: __('End_time'), addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'user_total', title: __('User_total'), operate: false},
                        {field: 'status', title:__('Status'), formatter: Table.api.formatter.toggle, operate: false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'edit_sort',
                                    title: __('排序'),
                                    text: __('排序'),
                                    classname: 'btn btn-xs btn-primary btn-dialog btn-edit-sort',
                                    url: 'activity/activity/edit_sort',
                                },
                                {
                                    name: 'edit',
                                    title: __('编辑'),
                                    text: __('编辑'),
                                    classname: 'btn btn-xs btn-primary btn-dialog btn-success',
                                    url: 'activity/activity/edit'
                                },
                                {
                                    name: 'show_video',
                                    title: __('查看视频'),
                                    text: __('查看视频'),
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    url: 'activity/topdata/index?activity_id={id}'
                                },
                                {
                                    name: 'top',
                                    title: __('活动排行榜'),
                                    text: __('活动排行榜'),
                                    classname: 'btn btn-xs btn-info btn-dialog btn-activity-top',
                                    url: function (data) {
                                        return 'activity/top/index?ids=' + data.id
                                    }

                                }
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            // 奖励方案
            $(document).on('click', '.reward_setting', function () {
                var name = $(this).attr('name');
                var input_name = '';
                if (name == 'video_apply_open') {
                    input_name = 'video_apply_val';
                } else if (name == 'video_like_open') {
                    input_name = 'video_like_val';
                } else if (name == 'video_play_open') {
                    input_name = 'video_play_val';
                }
                if (input_name != '') {
                    change_box_status($(this).prop('checked'), input_name);
                }
            });
            function change_box_status(box_status, input_name) {
                if (box_status) {
                    $('input[name='+ input_name +']').removeAttr('disabled')
                } else {
                    $('input[name='+ input_name +']').attr('disabled', 'disabled')
                }
            }
            Controller.api.bindevent();
        },
        del: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        edit_sort: function () {
            Controller.api.bindevent();
        },
        show_video: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function(data, ret){
                    Toastr.success(data);//成功
                }, function(data, ret){
                }, function(success, error){
                    var activity_details = $('#c-activity_details').html()
                    var share_details = $('#c-share_details').html()
                    var activity_rule = $('#c-activity_rule').html()
                    $('input[name=activity_details]').val(activity_details)
                    $('input[name=share_details]').val(share_details)
                    $('input[name=activity_rule]').val(activity_rule)
                });
            },
            events: {
            },
            formatter: {
                operate: function (value, row, index) {

                }
            }
        }
    };
    return Controller;
});