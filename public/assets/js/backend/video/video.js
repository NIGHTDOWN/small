define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/video/index',
                    // add_url: 'video/video/add',
                    // edit_url: 'video/video/edit',
                    del_url: 'video/video/del',
                    // multi_url: 'video/video/multi',
                    table: 'video',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                search: false,
                showToggle: false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'video.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title')},
                        {field: 'user.nickname', title: __('User_id'), operate: false},
                        {field: 'category_text', title: __('category_text'), operate: false},
                        {field: 'category_id', title: __('Category_id'), visible: false},
                        {field: 'user_view_total', title: __('User_view_total'), operate: false},
                        {field: 'user_like_total', title: __('User_like_total'), operate: false},
                        {field: 'user_comment_total', title: __('User_comment_total'), operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'process_done_time', title: __('时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: 'RANGE', visible: false},
                        {field: 'status_text', title: __('Status'), operate: false},
                        {
                            field: 'status',
                            title: __('Status'), 
                            searchList: {
                                '-1': __('删除'),
                                '0': __('未发布'),
                                '1': __('已发布'),
                                '2': __('机器审核未通过'),
                                '3': __('违规'),
                                '8': __('机器审核通过'),
                                '9': __('审核不通过'),
                                '10': __('草稿')
                            },
                            visible: false
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: 'set_category',
                                    title: __('设置分类'),
                                    text: __('设置分类'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/set_category'
                                },
                                {
                                    name: 'check_video',
                                    title: __('审核'),
                                    text: __('审核'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/check_video',
                                    hidden: function(row) {
                                        if (row.status != '2') {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'add_like_total',
                                    title: __('增加点赞数'),
                                    text: __('增加点赞数'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/add_like_total'
                                },
                                {
                                    name: 'set_title',
                                    title: __('视频标题'),
                                    text: __('视频标题'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/set_title'
                                },
                                {
                                    name: 'edit_cover_img',
                                    title: __('编辑封面'),
                                    text: __('编辑封面'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'video/video/edit_cover_img'
                                },
                                {
                                    name: 'play',
                                    title: __('查看视频'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-play',
                                    url: 'video/video/play'
                                },
                                {
                                    name: 'show',
                                    title: __('上架'),
                                    text: __('上架'),
                                    classname: 'btn btn-xs btn-warning btn-show',
                                    hidden: function(row) {
                                        if (row.status != '1') {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'hide',
                                    title: __('下架'),
                                    text: __('下架'),
                                    classname: 'btn btn-xs btn-warning btn-hide',
                                    hidden: function(row) {
                                        if (row.status != '0') {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'host',
                                    title: __('推荐'),
                                    text: __('推荐'),
                                    classname: 'btn btn-xs btn-warning btn-set_host',
                                    hidden: function(row) {
                                        if (row.status != '1' || row.recommend > '0') {
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
        set_category: function () {
            Controller.api.bindevent();
        },
        check_video: function () {
            Controller.api.bindevent();
        },
        add_like_total: function () {
            Controller.api.bindevent();
        },
        set_title: function () {
            Controller.api.bindevent();
        },
        play: function () {
            Controller.api.bindevent();
        },
        edit_cover_img: function () {
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
                    // 上架
                    'click .btn-show': function (e, value, row, index) {
                        Layer.confirm(
                            __('确定要上架吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                Controller.api.method.sendAjax(index, 'video/video/show', {id: row.id});
                            }
                        );
                    },
                    // 下架
                    'click .btn-hide': function (e, value, row, index) {
                        Layer.confirm(
                            __('确定要下架吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                Controller.api.method.sendAjax(index, 'video/video/hide');
                            }
                        );
                    },
                    // 推荐
                    'click .btn-set_host': function (e, value, row, index) {
                        Layer.confirm(
                            __('确定要推荐吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                Controller.api.method.sendAjax(index, 'video/video/host');
                            }
                        );
                    },
                    // 取消推荐
                    'click .btn-unhost': function (e, value, row, index) {
                        Layer.confirm(
                             __('确定要取消推荐吗?'),
                            {icon: 3, title: __('Warning'), offset: Controller.api.method.windowSize(this), shadeClose: true},
                            function (index) {
                                Controller.api.method.sendAjax(index, 'video/video/unhost');
                            }
                        );
                    }
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
                    buttons.forEach(function (item) {
                        names.push(item.name);
                    });
                    if (options.extend.del_url !== '' && names.indexOf('del') === -1) {
                        buttons.push({
                            name: 'del',
                            icon: 'fa fa-trash',
                            title: __('Del'),
                            extend: 'data-toggle="tooltip"',
                            classname: 'btn btn-xs btn-danger btn-delone'
                        });
                    }
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