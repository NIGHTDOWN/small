define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/hotvideo/index',
                    table: 'hot_video',
                }
            });

            var table = $("#table");
            var statusText = {0: '取消', 1: '正常'};

            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'hot_video.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'video.title', title: __('视频标题')},
                        {field: 'admin.nickname', title: __('Admin_id')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), formatter: function(data) {return statusText[data];}, operate: false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'play',
                                    title: __('查看视频'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'video/hotvideo/play'
                                },
                                {
                                    name: 'unhost',
                                    title: __('取消推荐'),
                                    text: __('取消推荐'),
                                    classname: 'btn btn-xs btn-warning btn-unhost',
                                    hidden: function (row) {
                                        if (row.status == '0') {
                                            return true;
                                        }
                                    }
                                }
                            ],
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            events: {
                operate: {
                    'click .btn-delone': function (e, value, row, index) {
                        var that = this;
                        Layer.confirm(
                            __('Are you sure you want to delete this item?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(that), shadeClose: true},
                            function (index) {
                                var table = $(that).closest('table');
                                var options = table.bootstrapTable('getOptions');
                                Table.api.multi("del", row[options.pk], table, that);
                                Layer.close(index);
                            }
                        );
                    },
                    // 取消推荐
                    'click .btn-unhost': function (e, value, row, index) {
                        Layer.confirm(
                            __('确定要取消推荐吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                Controller.api.method.sendAjax(index, 'video/video/hide', {id: row.video_id});
                            }
                        );
                    },
                }
            },
            formatter: {
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var buttons = $.extend([], this.buttons || []);
                    // 所有按钮名称
                    var names = [];
                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                }
            },
            method: {
                windowSize: function (obj) {
                    var top = $(obj).offset().top - $(window).scrollTop();
                    var left = $(obj).offset().left - $(window).scrollLeft() - 260;
                    if (top + 154 > $(window).height()) {
                        top = top - 154;
                    }
                    if ($(window).width() < 480) {
                        top = left = undefined;
                    }
                    return [top, left];
                },
                sendAjax: function (index, url, data = {}) {
                    Fast.api.ajax({
                        url: url,
                        type: 'POST',
                        data: data
                    }, function (data, result) {
                        Layer.close(index);
                        $('.btn-refresh').trigger('click');
                    }, function (data, result) {
                        Layer.close(index);
                    })
                }
            }
        }
    };
    return Controller;
});