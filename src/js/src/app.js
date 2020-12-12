import axios from "axios";

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

class WidgetRunner {
    async run(data, placeholders) {
        let res;
        try {
            res = await axios.post('/__widget2', {data, placeholders});
        } catch (e) {
            if(e.response.data.visible_message) {
                this.toastrError(e.response.data.visible_message);
                return;
            }
            if(e.response && e.response.data && e.response.data.exception) {
                await this.displayException(e.response.data);
                return;
            } else {
                (await this.toastr()).error('An error happened');
                return;
            }
        }

        eval(res.data);
    }

    async toastr() {
        await this.loadJs('https://cdn.jsdelivr.net/npm/jquery@3', window.jQuery);
        this.loadCss('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css', window.toastr);
        await this.loadJs('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js', window.toastr);
        return window.toastr;
    }

    async swalFire(options) {
        return (await this.swal()).fire(options);
    }

    async toastrSuccess(message) {
        (await this.toastr()).success(message);
    }

    async toastrError(message) {
        (await this.toastr()).error(message);
    }

    async swal() {
        await this.loadJs('https://cdn.jsdelivr.net/npm/sweetalert2@7', window.Swal);
        return window.Swal;
    }

    async displayException(e) {
        await this.swal();

        let traceHtml = '';
        for (const t of e.trace) {
            if (t.file) {
                traceHtml += `<div>${t.file}:${t.line}</div>`;
            } else {
                if (t.class) {
                    traceHtml += `<div>${t.class}::${t.function}</div>`;
                }
            }
        }

        Swal.fire({
            type: 'error',
            html:
            `
                <div style="font-size: 27px; font-weight: bold;">
                    ${e.message}
                </div>
                <div style="font-size: 14px; color: gray; margin-bottom: 1em;">(${e.exception})</div>
                ${traceHtml ? `<div style="text-align: left; background-color: #eee; padding: 1em; font-family: monospace; overflow: hidden; white-space: nowrap;">${traceHtml}</div>` : ``}
            `,
            width: 800,
        });
    }

    loadJs(src, ifndef) {
        if(!ifndef) {
            return new Promise((resolve, reject) => {
                var script = document.createElement('script');
                script.onload = function () {
                    resolve();
                };
                script.src = src;
                document.getElementsByTagName('head')[0].appendChild(script);
            });
        }
    }

    loadCss(src) {
        return new Promise((resolve, reject) => {
            var ls = document.createElement('link');
            ls.rel = "stylesheet";
            ls.href = src;
            ls.onload = function() {
                resolve();
            };
            document.getElementsByTagName('head')[0].appendChild(ls);
        });
    }
}


window.widgetRunner = new WidgetRunner;
