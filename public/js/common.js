(function (w, d, $, undefined) {

    $('.show-tooltip').tooltip();

    w.disableSelect = function(el) {
        el = $(el);
        el
            .attr('unselectable','on')
            .addClass('select-disabled')
            .bind('selectstart', function() { return false; })
        ;
    };

    Array.prototype.getUnique = function () {
        var u = {}, a = [];
        for (var i = 0, l = this.length; i < l; ++i) {
            if (u.hasOwnProperty(this[i])) {
                continue;
            }
            a.push(this[i]);
            u[this[i]] = 1;
        }
        return a;
    };

    Array.prototype.clean = function(deleteValue) {
        for (var i = 0; i < this.length; i++) {
            if (this[i] == deleteValue) {
                this.splice(i, 1);
                i--;
            }
        }
        return this;
    };

    String.prototype.toDDHHMMSS = function (showEmpty, showDays) {
        var sec_num = parseInt(this, 10); // don't forget the second param
        var days    = Math.floor(sec_num / 86400);
        var hours   = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        days = days > 0 ? '' + days : '0';
        if (hours   < 10) {hours   = "0" + hours;}
        if (minutes < 10) {minutes = "0" + minutes;}
        if (seconds < 10) {seconds = "0" + seconds;}

        var time = '';
        if (showDays) {
            if (showEmpty || days != '0') {
                time += days + ':';
            }
        }
        if (hours != '00' || time || showEmpty) {
            time += hours + ':';
        }
//        if (minutes != '00' || time || showEmpty) {
//            time += minutes + ':';
//        }
        time += minutes + ':';
        time += seconds;
        return time;
    }
    Number.prototype.toDDHHMMSS = String.prototype.toDDHHMMSS;

})(this, this.document, this.jQuery);
