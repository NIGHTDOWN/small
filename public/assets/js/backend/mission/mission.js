define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var base_data;
    $.ajax({
        url:'mission/mission/tableBaseData',
        async:false,
        success:function (data) {
            base_data = data;
        }
    });

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mission/mission/index',
                    add_url: 'mission/mission/add',
                    edit_url: 'mission/mission/edit',
                    // del_url: 'mission/mission/del',
                    multi_url: 'mission/mission/multi',
                    updatecache_url: 'mission/mission/updatecache',
                    table: 'mission',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),sortable: true},
                        {field: 'mission_group', title: __('任务组')},
                        {field: 'title', title: __('Title'),operate:'like'},
                        {field: 'mission_explain', title: __('Mission_explain'),operate:false},
                        {field: 'mission_tag', title: __('Mission_tag')},
                        {field: 'repeat_type', title: __('类型'),searchList: base_data.repeatTypeList,formatter:function (value) {
                            return base_data.repeatTypeList[value]
                        }},
                        {field: 'bonus_setting', title: __('奖励设置'),operate:false},
                        {field: 'bonus_limit', title: __('Bonus_limit')},
                        {field: 'quantity_condition', title: __('数量条件')},
                        {field: 'create_time', title: __('Create_time'), sortable: true, operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'),  sortable: true,operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('状态'),searchList: base_data.statusList,formatter:function (value) {
                            return base_data.statusList[value]
                        }},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'off',
                                    title: __('关闭'),
                                    text: __('关闭'),
                                    confirm:'确认关闭?',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    url: 'mission/mission/onoff/action/0',
                                    hidden: function(row) {
                                        if (row.status === 1) {
                                            return false;
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                },
                                {
                                    name: 'on',
                                    title: __('开启'),
                                    text: __('开启'),
                                    confirm:'确认开启?',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'mission/mission/onoff/action/1',
                                    hidden: function(row) {
                                        if (row.status === 0) {
                                            return false;
                                        }
                                        return true;
                                    },
                                    success: function (data, ret) {
                                        if (ret.code === 1){
                                            $('.btn-refresh').trigger('click');
                                        }
                                    }
                                }
                            ]
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
            }
        }
    };
    return Controller;
});