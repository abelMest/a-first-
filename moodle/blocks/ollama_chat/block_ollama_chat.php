<?php
defined('MOODLE_INTERNAL') || die();

class block_ollama_chat extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_ollama_chat');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '
        <div id="ollama-chat-box" style="height:300px;overflow-y:auto;border:1px solid #ccc;padding:10px;margin-bottom:10px;background:#f9f9f9;border-radius:6px;"></div>
        <input type="text" id="ollama-input" placeholder="Ask a question..." style="width:100%;padding:8px;margin-bottom:6px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
        <button onclick="ollamaSend()" style="width:100%;padding:8px;background:#0066cc;color:white;border:none;border-radius:4px;cursor:pointer;">Ask</button>

        <script>
        function ollamaSend() {
            var input = document.getElementById("ollama-input");
            var box = document.getElementById("ollama-chat-box");
            var question = input.value.trim();
            if (!question) return;

            box.innerHTML += "<p><strong>You:</strong> " + question + "</p>";
            input.value = "";
            box.scrollTop = box.scrollHeight;

            fetch("http://localhost:11434/api/generate", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    model: "llama3.2:1b",
                    prompt: "You are a public health informatics assistant for Africa CDC. Answer clearly and concisely. Question: " + question,
                    stream: false
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                var reply = data.response;
                box.innerHTML += "<p><strong>Assistant:</strong> " + reply + "</p>";
                box.scrollTop = box.scrollHeight;
            })
            .catch(function(error) {
                box.innerHTML += "<p style=\"color:red;\">Error: Could not reach Ollama. Make sure it is running.</p>";
                box.scrollTop = box.scrollHeight;
            });
        }

        document.getElementById("ollama-input").addEventListener("keypress", function(e) {
            if (e.key === "Enter") ollamaSend();
        });
        </script>';

        $this->content->footer = '';
        return $this->content;
    }

    public function applicable_formats() {
        return array('all' => true);
    }
}
