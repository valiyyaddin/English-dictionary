from flask import Flask, render_template
import pandas  as pd


df = pd.read_csv("dictionary.csv")

app=Flask(__name__)



@app.route("/")
def home():
   return  render_template("home.html")

@app.route('/api/v1/<word>/')
def about(word):

    definition=df.loc[df["word"] == word]["definition"].squeeze()
    result_dictionary={
        "definition": definition,
        "word": word
                       }


    return  result_dictionary


if __name__  == "__main__":
    app.run(debug=True)
